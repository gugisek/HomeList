<?php
// Wspólne funkcje modułu Opłaty: typy, cykle i terminy, kwota miesięczna, płatności (odhaczanie, dopisywanie
// automatycznych i wstecznych), lista nadchodzących, alerty „Wymaga uwagi”, podsumowanie i statystyki.
// Używane przez endpointy scripts/bills/*, widoki zakładki Opłaty, Today (do zapłaty dziś) i cron push.

if (!function_exists('hl_bill_types')) {
    // kolejność = kolejność kolorów na wykresach (sprawdzona pod daltonizm) – kolor idzie za typem, nigdy za miejscem w rankingu
    function hl_bill_types(): array
    {
        return [
            'loan' => ['label' => 'Rata / kredyt', 'group' => 'Raty i kredyty', 'short' => 'Raty', 'color' => '#f59e0b', 'cycle' => ['month', 1]],
            'sub' => ['label' => 'Subskrypcja', 'group' => 'Subskrypcje', 'short' => 'Subskr.', 'color' => '#6366f1', 'cycle' => ['month', 1]],
            'prepaid' => ['label' => 'Na kartę', 'group' => 'Na kartę', 'short' => 'Na kartę', 'color' => '#22c55e', 'cycle' => ['day', 30]],
            'pass' => ['label' => 'Karnet', 'group' => 'Karnety', 'short' => 'Karnety', 'color' => '#ec4899', 'cycle' => ['month', 1]],
            'plan' => ['label' => 'Abonament / umowa', 'group' => 'Abonamenty i umowy', 'short' => 'Umowy', 'color' => '#38bdf8', 'cycle' => ['month', 1]],
            'bill' => ['label' => 'Rachunek', 'group' => 'Rachunki', 'short' => 'Rachunki', 'color' => '#a855f7', 'cycle' => ['month', 1]],
            'ins' => ['label' => 'Ubezpieczenie', 'group' => 'Ubezpieczenia', 'short' => 'Ubezp.', 'color' => '#14b8a6', 'cycle' => ['year', 1]],
            'other' => ['label' => 'Inne', 'group' => 'Inne', 'short' => 'Inne', 'color' => '#64748b', 'cycle' => ['month', 1]],
        ];
    }

    // ---------- daty i cykle ----------

    function hl_bill_valid_date($s): bool
    {
        return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && strtotime($s) !== false;
    }

    // data przesunięta o $n miesięcy z zachowaniem dnia kotwicy (31 → 28/29/30 w krótszych miesiącach, potem znów 31)
    function hl_bill_add_months(string $date, int $n, ?int $anchorDay = null): string
    {
        $t = (int)substr($date, 0, 4) * 12 + (int)substr($date, 5, 2) - 1 + $n;
        $y = intdiv($t, 12);
        $m = $t % 12 + 1;
        $d = min($anchorDay ?? (int)substr($date, 8, 2), (int)date('t', mktime(0, 0, 0, $m, 1, $y)));
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    function hl_bill_add_days(string $date, int $n): string
    {
        return date('Y-m-d', strtotime($date . ' ' . ($n >= 0 ? '+' : '') . $n . ' day'));
    }

    // k-ty termin licząc od $from (k = 0 to sam $from); liczony zawsze od kotwicy, więc bez „pełzania” dnia
    function hl_bill_step(array $b, string $from, int $k): string
    {
        $every = max(1, (int)$b['cycle_every']);
        switch ($b['cycle_unit']) {
            case 'day': return hl_bill_add_days($from, $k * $every);
            case 'week': return hl_bill_add_days($from, $k * $every * 7);
            case 'year': return hl_bill_add_months($from, $k * $every * 12);
            default: return hl_bill_add_months($from, $k * $every);
        }
    }

    // długość cyklu w miesiącach (do przeliczania na miesiąc i rok)
    function hl_bill_cycle_months(array $b): float
    {
        $every = max(1, (int)$b['cycle_every']);
        switch ($b['cycle_unit']) {
            case 'day': return $every / 30.4375;
            case 'week': return $every * 7 / 30.4375;
            case 'year': return $every * 12;
            default: return $every;
        }
    }

    // „co miesiąc”, „co 3 miesiące”, „co 30 dni”, „co rok”
    function hl_bill_cycle_label(array $b): string
    {
        $n = max(1, (int)$b['cycle_every']);
        $forms = ['day' => ['dzień', 'dni', 'dni'], 'week' => ['tydzień', 'tygodnie', 'tygodni'], 'month' => ['miesiąc', 'miesiące', 'miesięcy'], 'year' => ['rok', 'lata', 'lat']];
        $f = $forms[$b['cycle_unit']] ?? $forms['month'];
        if ($n === 1) return 'co ' . $f[0];
        if ($b['cycle_unit'] === 'month' && $n === 3) return 'co kwartał';
        if ($b['cycle_unit'] === 'month' && $n === 6) return 'co pół roku';
        $form = ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14)) ? $f[1] : $f[2];
        return 'co ' . $n . ' ' . $form;
    }

    // ostatni termin wynikający z liczby rat (albo end_date, jeśli wcześniejszy)
    function hl_bill_last_due(array $b): ?string
    {
        $last = $b['end_date'] ?: null;
        if (!empty($b['installments']) && $b['anchor'] === 'schedule') {
            $byCount = hl_bill_step($b, $b['start_date'], (int)$b['installments'] - 1);
            $last = $last === null ? $byCount : min($last, $byCount);
        }
        if ($b['status'] === 'ended' && $b['ended_at']) $last = $last === null ? $b['ended_at'] : min($last, $b['ended_at']);
        return $last;
    }

    // terminy z harmonogramu w przedziale [from, to]; $lastPaid = dzień ostatniej płatności (anchor last_paid)
    function hl_bill_due_dates(array $b, string $from, string $to, ?string $lastPaid = null): array
    {
        $last = hl_bill_last_due($b);
        if ($last !== null && $last < $to) $to = $last;
        if ($to < $from) return [];
        if ($b['anchor'] === 'last_paid') {
            // na kartę: tylko jeden przyszły termin – ważność od ostatniego doładowania
            $next = $lastPaid ? hl_bill_step($b, $lastPaid, 1) : $b['start_date'];
            return $next >= $from && $next <= $to ? [$next] : [];
        }
        $out = [];
        $k = 0;
        // przeskok blisko $from, żeby nie iterować latami wstecz
        if ($from > $b['start_date']) {
            $k = max(0, (int)floor(((strtotime($from) - strtotime($b['start_date'])) / 86400) / (hl_bill_cycle_months($b) * 30.4375)) - 1);
        }
        for ($i = 0; $i < 3000; $i++, $k++) {
            $d = hl_bill_step($b, $b['start_date'], $k);
            if ($d > $to) break;
            if ($d >= $from) $out[] = $d;
        }
        return $out;
    }

    // numer raty dla terminu (1 = start_date)
    function hl_bill_installment_no(array $b, string $due): ?int
    {
        if (empty($b['installments']) || $b['anchor'] !== 'schedule') return null;
        $n = count(hl_bill_due_dates(array_merge($b, ['installments' => null, 'end_date' => null, 'status' => 'active']), $b['start_date'], $due));
        return $n ?: null;
    }

    // ---------- odczyt ----------

    // warunek widoczności opłat (na razie tylko właściciel; moduł „Dom” dołoży household_id)
    function hl_bill_visible_sql(int $userId, string $a = 'b'): string
    {
        return "{$a}.owner_id = " . (int)$userId;
    }

    function hl_bill_row(array $r): array
    {
        foreach (['id', 'owner_id', 'cycle_every', 'sort'] as $k) $r[$k] = (int)$r[$k];
        foreach (['household_id', 'notice_months', 'installments', 'pass_entries'] as $k) $r[$k] = $r[$k] !== null ? (int)$r[$k] : null;
        foreach (['amount_variable', 'auto_renew'] as $k) $r[$k] = $r[$k] !== null ? (bool)$r[$k] : null;
        $r['amount'] = (float)$r['amount'];
        foreach (['price_after', 'loan_rate', 'single_price'] as $k) $r[$k] = $r[$k] !== null ? (float)$r[$k] : null;
        $r['reminders'] = array_values(array_unique(array_map('intval', array_filter(explode(',', (string)$r['reminders']), 'strlen'))));
        return $r;
    }

    function hl_bill_get(mysqli $conn, int $userId, int $id): ?array
    {
        $stmt = $conn->prepare('SELECT b.* FROM bills b WHERE b.id = ? AND ' . hl_bill_visible_sql($userId));
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? hl_bill_row($row) : null;
    }

    // $statuses: które opłaty (domyślnie aktywne i wstrzymane)
    function hl_bill_all(mysqli $conn, int $userId, array $statuses = ['active', 'paused']): array
    {
        $in = implode(',', array_map(function ($s) { return "'" . preg_replace('/[^a-z]/', '', $s) . "'"; }, $statuses));
        $res = $conn->query('SELECT b.* FROM bills b WHERE ' . hl_bill_visible_sql($userId) . " AND b.status IN ($in) ORDER BY b.sort, b.id");
        $out = [];
        while ($row = $res->fetch_assoc()) $out[(int)$row['id']] = hl_bill_row($row);
        return $out;
    }

    // płatności opłat: bill_id => [due_date => [id, amount, status, source, paid_on]]
    function hl_bill_payments(mysqli $conn, array $billIds, ?string $from = null, ?string $to = null): array
    {
        if (!$billIds) return [];
        $sql = 'SELECT id, bill_id, due_date, paid_on, amount, status, source FROM bill_payments WHERE bill_id IN (' . implode(',', array_map('intval', $billIds)) . ')';
        if ($from) $sql .= " AND due_date >= '" . $conn->real_escape_string($from) . "'";
        if ($to) $sql .= " AND due_date <= '" . $conn->real_escape_string($to) . "'";
        $res = $conn->query($sql . ' ORDER BY due_date');
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[(int)$r['bill_id']][$r['due_date']] = ['id' => (int)$r['id'], 'amount' => (float)$r['amount'], 'status' => $r['status'], 'source' => $r['source'], 'paid_on' => $r['paid_on']];
        }
        return $out;
    }

    // dzień ostatniej opłaconej płatności każdej opłaty (anchor last_paid i „ostatnio zapłacono”)
    function hl_bill_last_paid(mysqli $conn, array $billIds): array
    {
        if (!$billIds) return [];
        $res = $conn->query("SELECT bill_id, MAX(COALESCE(paid_on, due_date)) FROM bill_payments WHERE status = 'paid' AND bill_id IN (" . implode(',', array_map('intval', $billIds)) . ') GROUP BY bill_id');
        $out = [];
        foreach ($res->fetch_all(MYSQLI_NUM) as [$id, $d]) $out[(int)$id] = $d;
        return $out;
    }

    // cena obowiązująca w dniu $date (historia cen, a bez niej bieżąca kwota)
    function hl_bill_price_on(array $prices, array $b, string $date): float
    {
        $p = null;
        foreach ($prices as $row) if ($row['valid_from'] <= $date) $p = $row['amount'];
        return $p ?? ($prices ? $prices[0]['amount'] : $b['amount']);
    }

    // bill_id => [[valid_from, amount], …] rosnąco
    function hl_bill_prices(mysqli $conn, array $billIds): array
    {
        if (!$billIds) return [];
        $res = $conn->query('SELECT bill_id, valid_from, amount FROM bill_prices WHERE bill_id IN (' . implode(',', array_map('intval', $billIds)) . ') ORDER BY valid_from');
        $out = [];
        while ($r = $res->fetch_assoc()) $out[(int)$r['bill_id']][] = ['valid_from' => $r['valid_from'], 'amount' => (float)$r['amount']];
        return $out;
    }

    // ---------- zapis płatności ----------

    // odhaczenie (albo pominięcie) terminu; $extras = [[label, amount], …] dolicza się do kwoty
    function hl_bill_pay(mysqli $conn, array $b, string $due, ?float $amount, int $userId, array $extras = [], string $status = 'paid', string $source = 'manual', ?string $paidOn = null): int
    {
        $base = $amount ?? $b['amount'];
        $total = $status === 'paid' ? round($base + array_sum(array_column($extras, 1)), 2) : 0.0;
        $paidOn = $status === 'paid' ? ($paidOn ?? date('Y-m-d')) : null;
        $stmt = $conn->prepare('INSERT INTO bill_payments (bill_id, due_date, paid_on, amount, status, source, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE paid_on = VALUES(paid_on), amount = VALUES(amount), status = VALUES(status), source = VALUES(source), user_id = VALUES(user_id)');
        $stmt->bind_param('issdssi', $b['id'], $due, $paidOn, $total, $status, $source, $userId);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare('SELECT id FROM bill_payments WHERE bill_id = ? AND due_date = ?');
        $stmt->bind_param('is', $b['id'], $due);
        $stmt->execute();
        $paymentId = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        $conn->query('DELETE FROM bill_payment_extras WHERE payment_id = ' . $paymentId);
        if ($status === 'paid' && $extras) {
            $stmt = $conn->prepare('INSERT INTO bill_payment_extras (payment_id, label, amount) VALUES (?, ?, ?)');
            foreach ($extras as [$label, $value]) {
                $label = mb_substr(trim((string)$label), 0, 80);
                $value = round((float)$value, 2);
                if ($label === '' || $value <= 0) continue;
                $stmt->bind_param('isd', $paymentId, $label, $value);
                $stmt->execute();
            }
            $stmt->close();
        }
        return $paymentId;
    }

    // cofnięcie odhaczenia (zwraca usunięty wiersz z dodatkami – do przywrócenia przez „Cofnij”)
    function hl_bill_unpay(mysqli $conn, array $b, string $due): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM bill_payments WHERE bill_id = ? AND due_date = ?');
        $stmt->bind_param('is', $b['id'], $due);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;
        $row['extras'] = $conn->query('SELECT label, amount FROM bill_payment_extras WHERE payment_id = ' . (int)$row['id'])->fetch_all(MYSQLI_NUM);
        $conn->query('DELETE FROM bill_payment_extras WHERE payment_id = ' . (int)$row['id']);
        $conn->query('DELETE FROM bill_payments WHERE id = ' . (int)$row['id']);
        return $row;
    }

    // dopisanie terminów od start_date do $until (włącznie) jako płatności z ceną z historii:
    // „płacę od…” przy dodawaniu (source estimate) i płatności automatyczne, które już minęły (source auto)
    function hl_bill_fill(mysqli $conn, array $b, string $until, string $source, ?array $prices = null, ?array $existing = null): int
    {
        if ($b['anchor'] === 'last_paid' || $b['amount_variable'] && $source === 'auto') return 0;
        $prices = $prices ?? (hl_bill_prices($conn, [$b['id']])[$b['id']] ?? []);
        $existing = $existing ?? (hl_bill_payments($conn, [$b['id']])[$b['id']] ?? []);
        $n = 0;
        $stmt = $conn->prepare('INSERT IGNORE INTO bill_payments (bill_id, due_date, paid_on, amount, status, source) VALUES (?, ?, ?, ?, \'paid\', ?)');
        foreach (hl_bill_due_dates($b, $b['start_date'], $until) as $due) {
            if (isset($existing[$due])) continue;
            $amount = hl_bill_price_on($prices, $b, $due);
            $stmt->bind_param('issds', $b['id'], $due, $due, $amount, $source);
            $stmt->execute();
            $n += $stmt->affected_rows;
        }
        $stmt->close();
        return $n;
    }

    // płatności automatyczne, których termin minął – dopisywane przy otwarciu widoku (i przez cron), bez zależności od crona
    function hl_bill_catch_up(mysqli $conn, int $userId, ?string $today = null): int
    {
        $today = $today ?? date('Y-m-d');
        $bills = array_filter(hl_bill_all($conn, $userId, ['active']), function ($b) { return $b['pay_mode'] === 'auto' && !$b['amount_variable'] && $b['anchor'] === 'schedule'; });
        if (!$bills) return 0;
        $ids = array_keys($bills);
        $prices = hl_bill_prices($conn, $ids);
        $payments = hl_bill_payments($conn, $ids);
        $n = 0;
        foreach ($bills as $id => $b) {
            if ($b['start_date'] > $today) continue;
            $n += hl_bill_fill($conn, $b, $today, 'auto', $prices[$id] ?? [], $payments[$id] ?? []);
        }
        return $n;
    }

    // ---------- kwoty ----------

    // ile opłata kosztuje miesięcznie: zmienne = średnia z 6 ostatnich płatności, na kartę = średnia z 3 ostatnich doładowań (z dodatkami)
    function hl_bill_monthly(array $b, array $payments = []): float
    {
        $paid = array_values(array_filter($payments, function ($p) { return $p['status'] === 'paid'; }));
        $per = $b['amount'];
        if (($b['amount_variable'] || $b['type'] === 'prepaid') && $paid) {
            $last = array_slice($paid, $b['type'] === 'prepaid' ? -3 : -6);
            $per = array_sum(array_column($last, 'amount')) / count($last);
        }
        return round($per / hl_bill_cycle_months($b), 2);
    }

    // ---------- widok: nadchodzące, alerty, podsumowanie ----------

    // nadchodzące i zaległe terminy: [[bill_id, due, amount, paid, overdue, installment], …] posortowane po terminie
    // zaległe = niezapłacone terminy ręcznych opłat z ostatnich 60 dni
    function hl_bill_upcoming(mysqli $conn, array $bills, string $today, int $days = 45): array
    {
        if (!$bills) return [];
        $ids = array_keys($bills);
        $from = hl_bill_add_days($today, -60);
        $to = hl_bill_add_days($today, $days);
        $payments = hl_bill_payments($conn, $ids, $from, $to);
        $lastPaid = hl_bill_last_paid($conn, $ids);
        $prices = hl_bill_prices($conn, $ids);
        $out = [];
        foreach ($bills as $id => $b) {
            if ($b['status'] !== 'active') continue;
            foreach (hl_bill_due_dates($b, max($from, $b['start_date']), $to, $lastPaid[$id] ?? null) as $due) {
                $p = $payments[$id][$due] ?? null;
                if ($due < $today && ($p || $b['pay_mode'] === 'auto')) continue;
                $out[] = [
                    'bill_id' => $id, 'due' => $due,
                    'amount' => $p && $p['status'] === 'paid' ? $p['amount'] : hl_bill_price_on($prices[$id] ?? [], $b, $due),
                    'estimate' => $b['amount_variable'] && !$p,
                    'paid' => $p !== null && $p['status'] === 'paid', 'skipped' => $p !== null && $p['status'] === 'skipped',
                    'overdue' => $due < $today && !$p,
                    'installment' => hl_bill_installment_no($b, $due),
                ];
            }
        }
        usort($out, function ($a, $b) { return [$a['due'], $a['bill_id']] <=> [$b['due'], $b['bill_id']]; });
        return $out;
    }

    // ostatni dzień na wypowiedzenie umowy
    function hl_bill_notice_deadline(array $b): ?string
    {
        if (!$b['contract_until'] || !$b['notice_months']) return null;
        return hl_bill_add_months($b['contract_until'], -$b['notice_months']);
    }

    // „Wymaga uwagi”: [[kind, bill_id, date, days], …] od najpilniejszych; $upcoming z hl_bill_upcoming
    function hl_bill_alerts(array $bills, array $upcoming, string $today): array
    {
        $out = [];
        $add = function (string $kind, array $b, string $date) use (&$out, $today) {
            $out[] = ['kind' => $kind, 'bill_id' => $b['id'], 'date' => $date, 'days' => (int)round((strtotime($date) - strtotime($today)) / 86400)];
        };
        $within = function (?string $date, int $days) use ($today) {
            return $date && $date >= $today && $date <= hl_bill_add_days($today, $days);
        };
        foreach ($bills as $b) {
            if ($b['status'] !== 'active') continue;
            if ($within($b['trial_until'], 3)) $add('trial', $b, $b['trial_until']);
            $notice = hl_bill_notice_deadline($b);
            if (!$b['contract_plan'] && $within($notice, 30)) $add('notice', $b, $notice);
            elseif ($within($b['contract_until'], $b['type'] === 'ins' ? 30 : 14)) $add($b['type'] === 'ins' ? 'insurance' : 'contract', $b, $b['contract_until']);
            if ($within($b['promo_until'], 30)) $add('promo', $b, $b['promo_until']);
            $last = hl_bill_last_due($b);
            if (!empty($b['installments']) && $within($last, 31)) $add('last_installment', $b, $last);
        }
        foreach ($upcoming as $u) {
            $b = $bills[$u['bill_id']];
            if ($u['overdue']) $add('overdue', $b, $u['due']);
            elseif ($b['type'] === 'prepaid' && !$u['paid'] && $within($u['due'], 7)) $add('prepaid', $b, $u['due']);
        }
        usort($out, function ($a, $b) { return [$a['kind'] !== 'overdue', $a['date']] <=> [$b['kind'] !== 'overdue', $b['date']]; });
        return $out;
    }

    // stałe koszty: miesięcznie łącznie i wg typu + ile jeszcze do zapłaty do końca miesiąca
    function hl_bill_summary(mysqli $conn, array $bills, array $upcoming, string $today): array
    {
        $payments = hl_bill_payments($conn, array_keys($bills), hl_bill_add_days($today, -400));
        $byType = array_fill_keys(array_keys(hl_bill_types()), 0.0);
        $monthly = [];
        foreach ($bills as $id => $b) {
            if ($b['status'] !== 'active') continue;
            $monthly[$id] = hl_bill_monthly($b, $payments[$id] ?? []);
            $byType[$b['type']] += $monthly[$id];
        }
        $monthEnd = date('Y-m-t', strtotime($today));
        $left = 0.0;
        foreach ($upcoming as $u) if (!$u['paid'] && !$u['skipped'] && $u['due'] <= $monthEnd) $left += $u['amount'];
        return ['monthly' => round(array_sum($monthly), 2), 'yearly' => round(array_sum($monthly) * 12, 2), 'by_type' => array_map(function ($v) { return round($v, 2); }, array_filter($byType)),
                'bill_monthly' => $monthly, 'left_this_month' => round($left, 2), 'count' => count($monthly)];
    }

    // ---------- statystyki ----------

    // faktyczne płatności w miesiącach [from, to] (YYYY-MM): ['months' => [ym => [type => suma]], 'total', 'by_type', 'prev_total']
    // prev_total = tyle samo miesięcy wcześniej (porównanie „niż rok wcześniej”)
    function hl_bill_stats(mysqli $conn, int $userId, string $fromYm, string $toYm): array
    {
        $types = hl_bill_types();
        $from = $fromYm . '-01';
        $to = date('Y-m-t', strtotime($toYm . '-01'));
        $span = (int)round((strtotime($to) - strtotime($from)) / 86400) + 1;
        $months = [];
        for ($d = $from; $d <= $to; $d = hl_bill_add_months($d, 1, 1)) $months[substr($d, 0, 7)] = [];
        $prevFrom = hl_bill_add_months($from, -count($months), 1);
        $stmt = $conn->prepare("SELECT DATE_FORMAT(p.due_date, '%Y-%m') ym, b.type, SUM(p.amount) FROM bill_payments p JOIN bills b ON b.id = p.bill_id
                                WHERE " . hl_bill_visible_sql($userId) . " AND p.status = 'paid' AND p.due_date BETWEEN ? AND ? GROUP BY ym, b.type");
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $byType = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$ym, $type, $sum]) {
            $months[$ym][$type] = round((float)$sum, 2);
            $byType[$type] = ($byType[$type] ?? 0) + (float)$sum;
        }
        $stmt->close();
        foreach ($months as &$m) uksort($m, function ($a, $b) use ($types) { return array_search($a, array_keys($types)) <=> array_search($b, array_keys($types)); });
        unset($m);
        arsort($byType);
        $stmt = $conn->prepare("SELECT SUM(p.amount) FROM bill_payments p JOIN bills b ON b.id = p.bill_id WHERE " . hl_bill_visible_sql($userId) . " AND p.status = 'paid' AND p.due_date >= ? AND p.due_date < ?");
        $stmt->bind_param('ss', $prevFrom, $from);
        $stmt->execute();
        $prev = (float)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        $total = array_sum($byType);
        return ['from' => $from, 'to' => $to, 'months' => $months, 'total' => round($total, 2), 'per_day' => round($total / max(1, $span), 2),
                'by_type' => array_map(function ($v) { return round($v, 2); }, $byType), 'prev_total' => round($prev, 2)];
    }

    // najdroższe opłaty od początku: [[bill_id, name, type, total, count, first_due], …]
    function hl_bill_top(mysqli $conn, int $userId, int $limit = 5): array
    {
        $stmt = $conn->prepare("SELECT b.id, b.name, b.type, b.status, SUM(p.amount) total, COUNT(*) n, MIN(p.due_date) first_due FROM bills b JOIN bill_payments p ON p.bill_id = b.id
                                WHERE " . hl_bill_visible_sql($userId) . " AND p.status = 'paid' GROUP BY b.id ORDER BY total DESC LIMIT ?");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $out = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $out[] = ['bill_id' => (int)$r['id'], 'name' => $r['name'], 'type' => $r['type'], 'status' => $r['status'], 'total' => round((float)$r['total'], 2), 'count' => (int)$r['n'], 'first_due' => $r['first_due']];
        }
        $stmt->close();
        return $out;
    }

    // podwyżki od $since: [[bill_id, from, to, valid_from, yearly_diff], …] – roczny skutek liczony z długości cyklu
    function hl_bill_price_changes(mysqli $conn, array $bills, string $since): array
    {
        $out = [];
        foreach (hl_bill_prices($conn, array_keys($bills)) as $id => $rows) {
            for ($i = 1; $i < count($rows); $i++) {
                if ($rows[$i]['valid_from'] < $since || $rows[$i]['amount'] == $rows[$i - 1]['amount']) continue;
                $diff = $rows[$i]['amount'] - $rows[$i - 1]['amount'];
                $out[] = ['bill_id' => $id, 'from' => $rows[$i - 1]['amount'], 'to' => $rows[$i]['amount'], 'valid_from' => $rows[$i]['valid_from'],
                          'yearly_diff' => round($diff * 12 / hl_bill_cycle_months($bills[$id]), 2)];
            }
        }
        usort($out, function ($a, $b) { return strcmp($b['valid_from'], $a['valid_from']); });
        return $out;
    }

    // statystyki jednej opłaty: suma narastająca, średnia, prognozy, ceny, dodatki, wejścia (karnet)
    function hl_bill_item_stats(mysqli $conn, array $b, string $today): array
    {
        $payments = hl_bill_payments($conn, [$b['id']])[$b['id']] ?? [];
        $series = [];
        $cum = 0.0;
        foreach ($payments as $due => $p) {
            if ($p['status'] !== 'paid') continue;
            $cum += $p['amount'];
            $series[] = ['due' => $due, 'amount' => $p['amount'], 'cum' => round($cum, 2), 'estimate' => $p['source'] === 'estimate'];
        }
        $first = $series[0]['due'] ?? null;
        $monthsSpan = $first ? max(1, (int)round((strtotime($today) - strtotime($first)) / 86400 / 30.4375)) : 0;
        $monthly = hl_bill_monthly($b, $payments);
        $remaining = null;
        if (!empty($b['installments'])) {
            $left = max(0, $b['installments'] - count($series));
            $remaining = ['count' => $left, 'amount' => round($left * $b['amount'], 2), 'last_due' => hl_bill_last_due($b)];
        }
        $visits = [];
        if ($b['type'] === 'pass') {
            $since = hl_bill_add_months(substr($today, 0, 7) . '-01', -5, 1);
            $stmt = $conn->prepare("SELECT DATE_FORMAT(visit_date, '%Y-%m'), COUNT(*) FROM bill_visits WHERE bill_id = ? AND visit_date >= ? GROUP BY 1");
            $stmt->bind_param('is', $b['id'], $since);
            $stmt->execute();
            $counts = [];
            foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$ym, $n]) $counts[$ym] = (int)$n;
            $stmt->close();
            for ($d = $since; $d <= $today; $d = hl_bill_add_months($d, 1, 1)) {
                $ym = substr($d, 0, 7);
                $n = $counts[$ym] ?? 0;
                $visits[] = ['ym' => $ym, 'count' => $n, 'per_visit' => $n ? round($monthly / $n, 2) : null];
            }
        }
        $extras = [];
        if ($payments) {
            $res = $conn->query('SELECT e.label, e.amount, p.due_date FROM bill_payment_extras e JOIN bill_payments p ON p.id = e.payment_id WHERE p.bill_id = ' . (int)$b['id'] . ' ORDER BY p.due_date DESC');
            foreach ($res->fetch_all(MYSQLI_ASSOC) as $e) $extras[] = ['label' => $e['label'], 'amount' => (float)$e['amount'], 'due' => $e['due_date']];
        }
        return [
            'series' => $series, 'total' => round($cum, 2), 'count' => count($series), 'first_due' => $first,
            'avg_monthly' => $monthsSpan ? round($cum / $monthsSpan, 2) : 0, 'monthly' => $monthly,
            'next_12' => round($monthly * 12, 2), 'next_60' => round($monthly * 60, 2),
            'prices' => hl_bill_prices($conn, [$b['id']])[$b['id']] ?? [], 'remaining' => $remaining, 'visits' => $visits, 'extras' => $extras,
            'estimated' => count(array_filter($series, function ($s) { return $s['estimate']; })),
        ];
    }

    // ekran statystyk: okres 12 (ostatnie 12 mies.) | year (od stycznia) | all (od pierwszej płatności)
    // + „Do przemyślenia”: subskrypcje za 5 lat, najbliższy koniec rat, podwyżki z ostatniego roku
    function hl_bill_stats_payload(mysqli $conn, int $userId, string $period, string $today): array
    {
        $period = in_array($period, ['12', 'year', 'all'], true) ? $period : '12';
        $toYm = substr($today, 0, 7);
        if ($period === 'year') $fromYm = substr($today, 0, 4) . '-01';
        elseif ($period === 'all') {
            $first = $conn->query('SELECT MIN(p.due_date) FROM bill_payments p JOIN bills b ON b.id = p.bill_id WHERE ' . hl_bill_visible_sql($userId) . " AND p.status = 'paid'")->fetch_row()[0];
            $fromYm = $first ? substr($first, 0, 7) : $toYm;
        } else $fromYm = substr(hl_bill_add_months($today, -11, 1), 0, 7);

        $bills = hl_bill_all($conn, $userId, ['active', 'paused', 'ended']);
        $active = array_filter($bills, function ($b) { return $b['status'] === 'active'; });
        $payments = hl_bill_payments($conn, array_keys($active), hl_bill_add_days($today, -400));

        $subMonthly = 0.0;
        $loanEnd = null;
        foreach ($active as $id => $b) {
            if ($b['type'] === 'sub') $subMonthly += hl_bill_monthly($b, $payments[$id] ?? []);
            if (empty($b['installments'])) continue;
            $last = hl_bill_last_due($b);
            if ($last < $today || ($loanEnd && $last >= $loanEnd['last_due'])) continue;
            $left = 0;
            foreach (hl_bill_due_dates($b, $today, $last) as $d) if (!isset($payments[$id][$d])) $left++;
            $loanEnd = ['bill_id' => $id, 'name' => $b['name'], 'last_due' => $last, 'monthly' => hl_bill_monthly($b), 'left_count' => $left, 'left_amount' => round($left * $b['amount'], 2)];
        }
        $changes = hl_bill_price_changes($conn, $bills, hl_bill_add_days($today, -365));
        foreach ($changes as &$c) $c['name'] = $bills[$c['bill_id']]['name'];
        unset($c);
        $types = [];
        foreach (hl_bill_types() as $k => $t) $types[$k] = ['label' => $t['group'], 'color' => $t['color']];
        return [
            'today' => $today, 'period' => $period, 'stats' => hl_bill_stats($conn, $userId, $fromYm, $toYm), 'top' => hl_bill_top($conn, $userId, 5),
            'price_changes' => $changes, 'price_changes_yearly' => round(array_sum(array_column($changes, 'yearly_diff')), 2),
            'subscriptions_5y' => round($subMonthly * 60, 2), 'loan_end' => $loanEnd, 'types' => $types,
        ];
    }

    // ---------- dane dla widoków (JSON) ----------

    // opłata w formacie dla JS (bez pól innych typów); $monthly z hl_bill_summary / hl_bill_monthly
    function hl_bill_public(array $b, ?float $monthly = null): array
    {
        $t = hl_bill_types()[$b['type']];
        $out = [
            'id' => $b['id'], 'type' => $b['type'], 'type_label' => $t['label'], 'color' => $t['color'],
            'name' => $b['name'], 'note' => $b['note'], 'ref' => $b['ref'], 'amount' => $b['amount'], 'amount_variable' => $b['amount_variable'],
            'cycle_unit' => $b['cycle_unit'], 'cycle_every' => $b['cycle_every'], 'cycle_label' => hl_bill_cycle_label($b),
            'pay_mode' => $b['pay_mode'], 'status' => $b['status'], 'ended_at' => $b['ended_at'], 'start_date' => $b['start_date'], 'end_date' => $b['end_date'],
            'reminders' => $b['reminders'], 'monthly' => $monthly ?? hl_bill_monthly($b), 'last_due' => hl_bill_last_due($b),
        ];
        foreach (hl_bill_type_fields()[$b['type']] as $k) $out[$k] = $b[$k];
        if ($b['notice_months']) $out['notice_deadline'] = hl_bill_notice_deadline($b);
        return $out;
    }

    // ekran główny: dopisuje minione płatności automatyczne, potem nadchodzące, alerty i podsumowanie
    function hl_bill_overview(mysqli $conn, int $userId, ?string $today = null): array
    {
        $today = $today ?? date('Y-m-d');
        hl_bill_catch_up($conn, $userId, $today);
        $bills = hl_bill_all($conn, $userId);
        $upcoming = hl_bill_upcoming($conn, $bills, $today);
        $summary = hl_bill_summary($conn, $bills, $upcoming, $today);
        $types = [];
        foreach (hl_bill_types() as $k => $t) $types[] = ['key' => $k] + array_diff_key($t, ['cycle' => 1]) + ['cycle_unit' => $t['cycle'][0], 'cycle_every' => $t['cycle'][1]];
        return [
            'today' => $today,
            'bills' => array_values(array_map(function ($b) use ($summary) { return hl_bill_public($b, $summary['bill_monthly'][$b['id']] ?? null); }, $bills)),
            'upcoming' => $upcoming, 'alerts' => hl_bill_alerts($bills, $upcoming, $today),
            'summary' => array_diff_key($summary, ['bill_monthly' => 1]), 'types' => $types,
            'due_count' => count(array_filter($upcoming, function ($u) use ($bills, $today) { return !$u['paid'] && !$u['skipped'] && $u['due'] <= $today && ($bills[$u['bill_id']]['pay_mode'] === 'manual' || $u['overdue']); })),
        ];
    }

    // ---------- zapis opłaty ----------

    // pola zależne od typu – reszta zapisywana jako NULL
    function hl_bill_type_fields(): array
    {
        return [
            'sub' => ['trial_until', 'review_at'],
            'plan' => ['contract_until', 'notice_months', 'promo_until', 'price_after', 'contract_plan'],
            'prepaid' => [],
            'loan' => ['installments', 'loan_rate'],
            'pass' => ['pass_entries', 'single_price'],
            'ins' => ['contract_until', 'auto_renew', 'contract_plan'],
            'bill' => [],
            'other' => ['contract_until'],
        ];
    }

    // walidacja formularza → [pola bills, dodatkowe: next_due, paid_from, paid_count] albo string z błędem
    function hl_bill_from_input(array $in, ?array $existing = null)
    {
        $types = hl_bill_types();
        $type = $existing['type'] ?? (string)($in['type'] ?? '');
        if (!isset($types[$type])) return 'Wybierz rodzaj opłaty';
        $name = trim(preg_replace('/\s+/u', ' ', (string)($in['name'] ?? '')));
        if ($name === '') return 'Podaj nazwę';
        $amount = hl_bill_float($in['amount'] ?? '', 0, 1000000);
        $variable = $type === 'bill' && !empty($in['amount_variable']);
        if ($amount === null || ($amount <= 0 && !$variable)) return 'Podaj kwotę';
        $date = function ($k) use ($in) { $v = (string)($in[$k] ?? ''); return hl_bill_valid_date($v) ? $v : null; };
        $int = function ($k, $min, $max) use ($in) { $v = $in[$k] ?? ''; return $v === '' || !is_numeric($v) ? null : max($min, min($max, (int)$v)); };

        [$unit, $every] = $types[$type]['cycle'];
        if (isset($in['cycle_unit']) && in_array($in['cycle_unit'], ['day', 'week', 'month', 'year'], true)) $unit = $in['cycle_unit'];
        if (isset($in['cycle_every'])) $every = max(1, min(400, (int)$in['cycle_every']));
        if ($type === 'loan') [$unit, $every] = [$unit === 'year' ? 'year' : 'month', $unit === 'year' ? 1 : $every];

        $reminders = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($in['reminders'] ?? '1'))), function ($d) { return $d >= 0 && $d <= 60; })));
        if (!isset($in['reminders'])) $reminders = $existing['reminders'] ?? [1];
        sort($reminders);

        $f = [
            'type' => $type, 'name' => mb_substr($name, 0, 120), 'note' => mb_substr(trim((string)($in['note'] ?? '')), 0, 500) ?: null,
            'ref' => mb_substr(trim((string)($in['ref'] ?? '')), 0, 60) ?: null,
            'amount' => $amount, 'amount_variable' => $variable ? 1 : 0, 'cycle_unit' => $unit, 'cycle_every' => $every,
            'anchor' => $type === 'prepaid' ? 'last_paid' : 'schedule',
            'pay_mode' => ($in['pay_mode'] ?? '') === 'auto' ? 'auto' : 'manual', 'reminders' => implode(',', $reminders),
            'end_date' => $date('end_date'),
            'trial_until' => $date('trial_until'), 'review_at' => $existing['review_at'] ?? null,
            'contract_until' => $date('contract_until'), 'notice_months' => $int('notice_months', 0, 24) ?: null,
            'promo_until' => $date('promo_until'), 'price_after' => hl_bill_float($in['price_after'] ?? '', 0, 1000000),
            'auto_renew' => isset($in['auto_renew']) && $in['auto_renew'] !== '' ? (int)!empty($in['auto_renew']) : null,
            'contract_plan' => $existing['contract_plan'] ?? null,
            'installments' => $int('installments', 1, 600), 'loan_rate' => hl_bill_float($in['loan_rate'] ?? '', 0, 100),
            'pass_entries' => $int('pass_entries', 1, 400), 'single_price' => hl_bill_float($in['single_price'] ?? '', 0, 10000),
        ];
        $keep = hl_bill_type_fields()[$type];
        foreach (['trial_until', 'review_at', 'contract_until', 'notice_months', 'promo_until', 'price_after', 'auto_renew', 'contract_plan', 'installments', 'loan_rate', 'pass_entries', 'single_price'] as $k) {
            if (!in_array($k, $keep, true)) $f[$k] = null;
        }
        if ($type === 'loan' && !$f['installments']) return 'Podaj liczbę rat';
        if ($f['contract_until'] && $f['contract_until'] < '2000-01-01') return 'Nieprawidłowa data końca umowy';

        $extra = ['next_due' => $date('next_due'), 'paid_from' => $date('paid_from'), 'paid_count' => $int('paid_count', 0, 600)];
        if (!$existing && !$extra['next_due']) return 'Podaj termin najbliższej płatności';
        if ($extra['next_due'] && $extra['next_due'] < date('Y-m-d', strtotime('-1 year'))) return 'Najbliższa płatność nie może być tak dawno';
        if ($type === 'loan' && $extra['paid_count'] !== null && $extra['paid_count'] >= $f['installments']) return 'Zapłaconych rat musi być mniej niż wszystkich';
        return ['fields' => $f, 'extra' => $extra];
    }

    // pierwszy termin harmonogramu, gdy znamy najbliższą płatność: rata → cofnięcie o liczbę zapłaconych rat,
    // „płacę od” → najwcześniejszy termin nie wcześniej niż ta data, inaczej sama najbliższa płatność
    function hl_bill_start_for(array $f, string $nextDue, ?string $paidFrom, ?int $paidCount): string
    {
        if ($f['type'] === 'loan' && $paidCount) return hl_bill_step($f, $nextDue, -$paidCount);
        if (!$paidFrom || $paidFrom >= $nextDue) return $nextDue;
        $start = $nextDue;
        for ($k = 1; $k < 3000; $k++) {
            $d = hl_bill_step($f, $nextDue, -$k);
            if ($d < $paidFrom) break;
            $start = $d;
        }
        return $start;
    }

    // dodanie albo edycja opłaty; zwraca id. Nowa opłata z historią wstecz dostaje płatności „estimate” przed najbliższym terminem.
    function hl_bill_save(mysqli $conn, int $userId, array $f, array $extra, ?array $existing = null): int
    {
        $today = date('Y-m-d');
        $cols = ['type', 'name', 'note', 'ref', 'amount', 'amount_variable', 'cycle_unit', 'cycle_every', 'anchor', 'start_date', 'end_date', 'pay_mode', 'reminders',
                 'trial_until', 'review_at', 'contract_until', 'notice_months', 'promo_until', 'price_after', 'auto_renew', 'contract_plan', 'installments', 'loan_rate', 'pass_entries', 'single_price'];
        if ($existing) {
            $f['start_date'] = $existing['start_date'];
            $scheduleChanged = $f['cycle_unit'] !== $existing['cycle_unit'] || $f['cycle_every'] !== $existing['cycle_every'];
            if ($extra['next_due'] || $scheduleChanged) {
                // nowy harmonogram od najbliższej płatności; rata zachowuje numerację (start = cofnięcie o zapłacone raty)
                $next = $extra['next_due'] ?: (hl_bill_upcoming($conn, [$existing['id'] => $existing], $today)[0]['due'] ?? $today);
                $paid = $f['type'] === 'loan' ? (int)$conn->query("SELECT COUNT(*) FROM bill_payments WHERE status = 'paid' AND bill_id = " . (int)$existing['id'] . " AND due_date < '" . $conn->real_escape_string($next) . "'")->fetch_row()[0] : 0;
                $f['start_date'] = hl_bill_start_for($f, $next, null, $paid ?: null);
            }
        } else {
            $f['start_date'] = hl_bill_start_for($f, $extra['next_due'], $extra['paid_from'], $extra['paid_count']);
        }
        $set = [];
        foreach ($cols as $c) $set[$c] = $f[$c] ?? null;
        $sqlType = function ($v) { return is_int($v) ? 'i' : (is_float($v) ? 'd' : 's'); };
        if ($existing) {
            $stmt = $conn->prepare('UPDATE bills SET ' . implode(', ', array_map(function ($c) { return "`$c` = ?"; }, $cols)) . ' WHERE id = ?');
            $vals = array_values($set);
            $vals[] = $existing['id'];
            $stmt->bind_param(implode('', array_map($sqlType, $vals)), ...$vals);
            $stmt->execute();
            $stmt->close();
            $id = $existing['id'];
            if ((float)$f['amount'] !== $existing['amount']) {
                // nowa cena obowiązuje od najbliższej niezapłaconej płatności (historia zostaje)
                $from = $extra['next_due'] ?: $today;
                $stmt = $conn->prepare('INSERT INTO bill_prices (bill_id, amount, valid_from) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)');
                $stmt->bind_param('ids', $id, $f['amount'], $from);
                $stmt->execute();
                $stmt->close();
            }
            return $id;
        }
        $vals = array_merge([$userId], array_values($set));
        $stmt = $conn->prepare('INSERT INTO bills (owner_id, ' . implode(', ', array_map(function ($c) { return "`$c`"; }, $cols)) . ') VALUES (' . implode(', ', array_fill(0, count($vals), '?')) . ')');
        $stmt->bind_param(implode('', array_map($sqlType, $vals)), ...$vals);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        $stmt->close();
        $stmt = $conn->prepare('INSERT INTO bill_prices (bill_id, amount, valid_from) VALUES (?, ?, ?)');
        $stmt->bind_param('ids', $id, $f['amount'], $f['start_date']);
        $stmt->execute();
        $stmt->close();

        $b = hl_bill_get($conn, $userId, $id);
        if ($b['start_date'] < $extra['next_due']) {
            if ($b['anchor'] === 'last_paid') {
                // na kartę: doładowania co cykl wstecz od najbliższego wygaśnięcia (paid_on = termin)
                $stmt = $conn->prepare("INSERT IGNORE INTO bill_payments (bill_id, due_date, paid_on, amount, status, source) VALUES (?, ?, ?, ?, 'paid', 'estimate')");
                for ($k = 1; ($d = hl_bill_step($b, $extra['next_due'], -$k)) >= $b['start_date'] && $k < 3000; $k++) {
                    $stmt->bind_param('issd', $id, $d, $d, $b['amount']);
                    $stmt->execute();
                }
                $stmt->close();
                $conn->query("UPDATE bills SET start_date = '" . $conn->real_escape_string($extra['next_due']) . "' WHERE id = $id");
            } else {
                hl_bill_fill($conn, $b, hl_bill_add_days($extra['next_due'], -1), 'estimate');
            }
        }
        return $id;
    }

    // ile ręcznych płatności czeka do odhaczenia (dziś + zaległe) – kropka i licznik w nawigacji
    function hl_bill_due_count(mysqli $conn, int $userId, ?string $today = null): int
    {
        $today = $today ?? date('Y-m-d');
        try {
            $bills = array_filter(hl_bill_all($conn, $userId, ['active']), function ($b) { return $b['pay_mode'] === 'manual'; });
        } catch (mysqli_sql_exception $e) {
            return 0; // przed migracją
        }
        if (!$bills) return 0;
        return count(array_filter(hl_bill_upcoming($conn, $bills, $today, 0), function ($u) use ($today) { return !$u['paid'] && !$u['skipped'] && $u['due'] <= $today; }));
    }

    // ---------- zakładka Dziś (today.php) ----------

    // płatności pokazywane w planie dnia:
    //   dziś       – ręczne na dziś + zaległe (także już odhaczone dziś, żeby nie znikały spod palca)
    //   przyszłość – wszystkie terminy tego dnia (automatyczne jako informacja)
    //   przeszłość – zapłacone tego dnia (historia)
    function hl_bill_day_items(mysqli $conn, int $userId, string $date, string $today): array
    {
        try {
            $bills = hl_bill_all($conn, $userId, ['active']);
        } catch (mysqli_sql_exception $e) {
            return []; // przed migracją
        }
        if (!$bills) return [];
        $types = hl_bill_types();
        $item = function (array $b, string $due, float $amount, array $extra) use ($types) {
            return $extra + ['bill_id' => $b['id'], 'name' => $b['name'], 'type' => $b['type'], 'color' => $types[$b['type']]['color'], 'due' => $due, 'amount' => $amount,
                             'pay_mode' => $b['pay_mode'], 'variable' => $b['amount_variable'], 'prepaid' => $b['type'] === 'prepaid',
                             'installment' => hl_bill_installment_no($b, $due), 'installments' => $b['installments'], 'paid' => false, 'overdue' => false, 'estimate' => false];
        };
        $out = [];
        if ($date < $today) {
            $all = hl_bill_all($conn, $userId, ['active', 'paused', 'ended']);
            foreach (hl_bill_payments($conn, array_keys($all), $date, $date) as $id => $rows) {
                foreach ($rows as $due => $p) if ($p['status'] === 'paid') $out[] = $item($all[$id], $due, $p['amount'], ['paid' => true]);
            }
            return $out;
        }
        $days = (int)round((strtotime($date) - strtotime($today)) / 86400);
        foreach (hl_bill_upcoming($conn, $bills, $today, max(0, $days)) as $u) {
            $b = $bills[$u['bill_id']];
            if ($u['skipped']) continue;
            if ($date === $today) {
                if ($u['due'] > $today || ($b['pay_mode'] === 'auto' && !$u['overdue'])) continue;
            } elseif ($u['due'] !== $date) continue;
            $out[] = $item($b, $u['due'], $u['amount'], ['paid' => $u['paid'], 'overdue' => $u['overdue'], 'estimate' => $u['estimate']]);
        }
        usort($out, function ($a, $b) { return [$b['overdue'], $a['paid'], $a['due']] <=> [$a['overdue'], $b['paid'], $b['due']]; });
        return $out;
    }

    // dni z płatnościami w pasku dni: date => liczba (przeszłe = zapłacone, przyszłe = terminy aktywnych opłat)
    function hl_bill_days(mysqli $conn, int $userId, string $from, string $to, string $today): array
    {
        try {
            $all = hl_bill_all($conn, $userId, ['active', 'paused', 'ended']);
        } catch (mysqli_sql_exception $e) {
            return [];
        }
        if (!$all) return [];
        $out = [];
        foreach (hl_bill_payments($conn, array_keys($all), $from, min($to, hl_bill_add_days($today, -1))) as $rows) {
            foreach ($rows as $due => $p) if ($p['status'] === 'paid') $out[$due] = ($out[$due] ?? 0) + 1;
        }
        $active = array_filter($all, function ($b) { return $b['status'] === 'active'; });
        if ($to >= $today && $active) {
            $lastPaid = hl_bill_last_paid($conn, array_keys($active));
            $payments = hl_bill_payments($conn, array_keys($active), $today, $to);
            foreach ($active as $id => $b) {
                foreach (hl_bill_due_dates($b, max($from, $today), $to, $lastPaid[$id] ?? null) as $d) {
                    if (($payments[$id][$d]['status'] ?? '') === 'skipped') continue;
                    $out[$d] = ($out[$d] ?? 0) + 1;
                }
            }
        }
        return $out;
    }

    // kalendarz miesiąca (PC): date => [[bill_id, name, type, amount, paid, estimate], …]
    // przeszłe dni = zapłacone płatności, dziś i dalej = terminy aktywnych opłat (z informacją, czy już opłacone)
    function hl_bill_month(mysqli $conn, int $userId, string $ym, string $today): array
    {
        $from = $ym . '-01';
        $to = date('Y-m-t', strtotime($from));
        $all = hl_bill_all($conn, $userId, ['active', 'paused', 'ended']);
        if (!$all) return [];
        $out = [];
        $add = function (array $b, string $d, float $amount, bool $paid, bool $estimate) use (&$out) {
            $out[$d][] = ['bill_id' => $b['id'], 'name' => $b['name'], 'type' => $b['type'], 'amount' => round($amount, 2), 'paid' => $paid, 'estimate' => $estimate];
        };
        $payments = hl_bill_payments($conn, array_keys($all), $from, $to);
        foreach ($payments as $id => $rows) {
            foreach ($rows as $d => $p) if ($p['status'] === 'paid' && ($d < $today || $all[$id]['status'] !== 'active')) $add($all[$id], $d, $p['amount'], true, false);
        }
        if ($to >= $today) {
            $active = array_filter($all, function ($b) { return $b['status'] === 'active'; });
            $lastPaid = hl_bill_last_paid($conn, array_keys($active));
            $prices = hl_bill_prices($conn, array_keys($active));
            foreach ($active as $id => $b) {
                foreach (hl_bill_due_dates($b, max($from, $today), $to, $lastPaid[$id] ?? null) as $d) {
                    $p = $payments[$id][$d] ?? null;
                    if ($p && $p['status'] === 'skipped') continue;
                    $add($b, $d, $p ? $p['amount'] : hl_bill_price_on($prices[$id] ?? [], $b, $d), (bool)$p, !$p && $b['amount_variable']);
                }
            }
        }
        ksort($out);
        return $out;
    }

    // ---------- powiadomienia push (scripts/push/cron.php, raz dziennie o porze „Rano”) ----------

    // „dziś” / „jutro” / „za 3 dni”
    function hl_bill_in_days(int $n): string
    {
        return $n === 0 ? 'dziś' : ($n === 1 ? 'jutro' : 'za ' . $n . ' dni');
    }

    // zdarzenia do przypomnienia dziś: [['key' => unikalny klucz (push_log), 'bill_id', 'name', 'text'], …]
    // płatności wg reminders opłaty (dni przed terminem), zaległe ręczne (1, 3, 7 dni po), koniec okresu próbnego (dzień przed i w dniu),
    // wypowiedzenie (14, 3, 0 dni przed), koniec umowy (7), polisa (30, 7), promocja (7)
    function hl_bill_push_events(mysqli $conn, int $userId, string $today): array
    {
        $bills = hl_bill_all($conn, $userId, ['active']);
        if (!$bills) return [];
        $days = function (?string $date) use ($today) { return $date ? (int)round((strtotime($date) - strtotime($today)) / 86400) : null; };
        $out = [];
        $add = function (array $b, string $kind, string $ref, string $text) use (&$out) {
            $out[] = ['key' => 'bill' . $b['id'] . '_' . $kind . '_' . $ref, 'bill_id' => $b['id'], 'name' => $b['name'], 'text' => $text];
        };
        foreach (hl_bill_upcoming($conn, $bills, $today, 31) as $u) {
            $b = $bills[$u['bill_id']];
            if ($u['paid'] || $u['skipped']) continue;
            $n = $days($u['due']);
            $amount = ($u['estimate'] ? '~' : '') . hl_bill_money($u['amount']);
            if ($u['overdue']) {
                if (in_array(-$n, [1, 3, 7], true)) $add($b, 'late', $u['due'] . '_' . (-$n), 'zaległa od ' . (-$n) . ' ' . (-$n === 1 ? 'dnia' : 'dni') . ' · ' . $amount);
                continue;
            }
            if (!in_array($n, $b['reminders'], true)) continue;
            if ($b['type'] === 'prepaid') {
                $text = $n ? 'pakiet ważny jeszcze ' . $n . ' ' . ($n === 1 ? 'dzień' : 'dni') . ' – doładuj ' . $amount : 'pakiet kończy się dziś – doładuj ' . $amount;
            } elseif ($b['pay_mode'] === 'auto') {
                $text = $amount . ' pobierze się ' . hl_bill_in_days($n);
            } else {
                $text = $amount . ' do zapłaty ' . hl_bill_in_days($n);
            }
            if ($u['installment'] && $b['installments']) $text .= $u['installment'] === $b['installments'] ? ' · ostatnia rata' : ' · rata ' . $u['installment'] . ' z ' . $b['installments'];
            $add($b, 'pay', $u['due'] . '_' . $n, $text);
        }
        foreach ($bills as $b) {
            $n = $days($b['trial_until']);
            if ($n === 0 || $n === 1) $add($b, 'trial', $b['trial_until'] . '_' . $n, 'okres próbny kończy się ' . hl_bill_in_days($n) . ' – potem ' . hl_bill_money($b['amount']) . ' ' . hl_bill_cycle_label($b));
            $notice = hl_bill_notice_deadline($b);
            $n = $days($notice);
            if (in_array($n, [14, 3, 0], true) && in_array($b['contract_plan'], [null, 'quit'], true)) {
                $add($b, 'notice', $notice . '_' . $n, ($b['contract_plan'] === 'quit' ? 'wyślij wypowiedzenie – ' : '') . ($n ? 'ostatni dzień na wypowiedzenie ' . hl_bill_in_days($n) : 'dziś ostatni dzień na wypowiedzenie'));
            }
            $n = $days($b['contract_until']);
            if ($b['type'] === 'ins' && in_array($n, [30, 7], true)) $add($b, 'ins', $b['contract_until'] . '_' . $n, 'polisa kończy się ' . hl_bill_in_days($n) . ' – dobry moment na porównanie ofert');
            elseif ($b['type'] !== 'ins' && $n === 7 && $b['contract_plan'] !== 'quit') $add($b, 'end', $b['contract_until'], 'umowa kończy się za tydzień');
            $n = $days($b['promo_until']);
            if ($n === 7) $add($b, 'promo', $b['promo_until'], 'koniec promocji za tydzień' . ($b['price_after'] ? ' – potem ' . hl_bill_money($b['price_after']) : ''));
        }
        return $out;
    }

    // ---------- pomocnicze ----------

    function hl_bill_float($v, float $min, float $max): ?float
    {
        $v = str_replace([',', ' ', "\xc2\xa0"], ['.', '', ''], trim((string)$v));
        if ($v === '' || !is_numeric($v)) return null;
        $f = round((float)$v, 2);
        return $f < $min || $f > $max ? null : $f;
    }

    // „1 427,79 zł”
    function hl_bill_money(float $v, bool $decimals = true): string
    {
        return number_format($v, $decimals ? 2 : 0, ',', "\u{00a0}") . "\u{00a0}zł";
    }

    function hl_bill_json(string $status, string $message = '', array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
