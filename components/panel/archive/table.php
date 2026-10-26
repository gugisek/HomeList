<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['logs', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Dziennik zmian (logs) – zakładka „Zmiany” w Archiwum, 20 wpisów na stronę
include '../../../scripts/conn_db.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$total_records = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT count(id) FROM logs"))[0];
$total_pages = max(1, (int)ceil($total_records / 20));
$page_id = min($total_pages, max(1, (int)($_GET['page_id'] ?? 1)));
$result = mysqli_query($conn, "SELECT logs.id, users.name, users.sur_name, logs.when, logs.object_id, logs.object_type, log_types.type, logs.description FROM `logs` LEFT JOIN users ON users.id = logs.user_id JOIN log_types ON log_types.id = logs.type ORDER BY id DESC LIMIT 20 OFFSET ".(($page_id - 1) * 20));

$types = [
    'create' => ['Dodanie', 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-400/15'],
    'modify' => ['Zmiana', 'text-sky-600 bg-sky-100 dark:text-sky-400 dark:bg-sky-400/15'],
    'delete' => ['Usunięcie', 'text-rose-600 bg-rose-100 dark:text-rose-400 dark:bg-rose-400/15'],
];
$pager_btn = 'hl-icon-btn hl-icon-btn-sm';
?>
<div class="flex items-center justify-end gap-1 mb-3 text-sm text-gray-400">
    <button type="button" onclick="openPageArchive(<?=max(1, $page_id - 1)?>)" title="Nowsze" class="<?=$pager_btn?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg></button>
    <span class="px-1">Strona
        <input id="hide_arrows" type="number" min="1" max="<?=$total_pages?>" value="<?=$page_id?>" onchange="openPageArchive(this.value)" class="hl-input !inline-block !w-14 !min-h-0 !py-1 !px-2 text-center mx-1">
        z <?=$total_pages?></span>
    <button type="button" onclick="openPageArchive(<?=min($total_pages, $page_id + 1)?>)" title="Starsze" class="<?=$pager_btn?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg></button>
</div>

<div class="hl-card p-2" data-aos="fade-in" data-aos-delay="100">
    <div class="hidden lg:grid grid-cols-12 gap-4 px-4 pt-3 pb-2">
        <span class="hl-eyebrow col-span-3">Kto</span>
        <span class="hl-eyebrow col-span-2">Kiedy</span>
        <span class="hl-eyebrow col-span-4">Opis</span>
        <span class="hl-eyebrow col-span-2">Obiekt</span>
        <span class="hl-eyebrow col-span-1 text-right">Akcja</span>
    </div>
    <div class="hl-divide">
        <?php if (mysqli_num_rows($result) === 0): ?>
        <p class="py-10 text-center text-sm text-gray-400">Brak wpisów</p>
        <?php endif; ?>
        <?php while ($row = mysqli_fetch_assoc($result)):
            [$type_label, $type_cls] = $types[$row['type']] ?? [$row['type'], ''];
        ?>
        <div onclick="openPopupLogs(<?=(int)$row['id']?>)" class="hl-row lg:grid lg:grid-cols-12 lg:gap-4">
            <div class="min-w-0 flex-1 lg:col-span-3">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"><?=$e(trim($row['name'].' '.$row['sur_name']) ?: '—')?></p>
                <p class="lg:hidden text-xs text-gray-400 truncate"><?=$e(date('d.m.Y H:i', strtotime($row['when'])))?> · <?=$e($row['object_type'])?> #<?=(int)$row['object_id']?></p>
            </div>
            <span class="hidden lg:block lg:col-span-2 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap"><?=$e(date('d.m.Y H:i', strtotime($row['when'])))?></span>
            <span class="hidden lg:block lg:col-span-4 text-sm text-gray-500 dark:text-gray-400 truncate"><?=$e($row['description'])?></span>
            <span class="hidden lg:block lg:col-span-2 text-sm text-gray-500 dark:text-gray-400 truncate"><?=$e($row['object_type'])?> <span class="text-gray-400">#<?=(int)$row['object_id']?></span></span>
            <span class="lg:col-span-1 lg:justify-self-end hl-badge flex-none <?=$type_cls?>"><?=$e($type_label)?></span>
        </div>
        <?php endwhile; ?>
    </div>
</div>
