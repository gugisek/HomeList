<?php
// Polecenie dla czatu AI (GET) – „Policz ze zdjęcia”: użytkownik kopiuje je do ChatGPT razem ze zdjęciem i wkleja z powrotem JSON.
// Słownik = nazwy produktów podstawowych HomeList (te same, co w wyszukiwarce), więc większość składników dopasuje się 1:1;
// do tego produkty, które użytkownik jada najczęściej. {{NOTE}} uzupełnia przeglądarka (posiłek, uwagi użytkownika).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$base = array_column($conn->query("SELECT name FROM food_products WHERE source = 'base' AND status = 'approved' ORDER BY id")->fetch_all(MYSQLI_ASSOC), 'name');
$vis = hl_food_visible_sql($conn, $user_id);
$frequent = array_map(fn($r) => $r['name'] . ($r['brand'] ? ' (' . $r['brand'] . ')' : ''), $conn->query(
    "SELECT p.name, p.brand FROM food_products p
     JOIN (SELECT product_id, COUNT(*) AS cnt FROM food_log WHERE user_id = $user_id AND product_id IS NOT NULL AND created_at > NOW() - INTERVAL 120 DAY
           GROUP BY product_id) u ON u.product_id = p.id
     WHERE $vis AND p.source IN ('off', 'user') ORDER BY u.cnt DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC));

$prompt = <<<'TXT'
Jesteś dietetykiem. Na zdjęciu (albo zdjęciach) jest mój posiłek. Oszacuj, co na nim jest i ile to waży. Odpowiedź wkleję do aplikacji HomeList, która sama dopasuje produkty do swojej bazy – dlatego zwróć WYŁĄCZNIE jeden blok kodu JSON, bez żadnego tekstu przed nim ani po nim.

Jak liczyć:
1. Rozbij posiłek na osobne składniki, które da się zważyć (np. kanapka → chleb, masło, szynka, pomidor). Jedna pozycja = jeden produkt. Wyjątek: gotowe danie ze SŁOWNIKA (np. Pierogi ruskie, Pizza margherita), gdy składników nie widać osobno.
2. Waga w gramach (napoje: w ml) części jadalnej, w stanie jak na talerzu – ryż, makaron i kasze ugotowane, mięso po obróbce. Szacuj z wielkości talerza, sztućców, dłoni, opakowań. Dla rzeczy policzalnych (jajka, kromki, parówki, plastry) podaj też liczbę i jednostkę: sztuka, kromka, plaster, łyżka, łyżeczka, szklanka, garść. "grams" to zawsze waga łączna.
3. Dolicz ukryte kalorie, których nie widać, a prawie na pewno są: tłuszcz do smażenia, masło na pieczywie, sos, dressing, cukier w napoju – jako osobne pozycje z pewnością "low".
4. Nazwy krótkie, ogólne, po polsku. Jeśli składnik pasuje do pozycji ze SŁOWNIKA HOMELIST, przepisz jej nazwę dokładnie, znak w znak. Jeśli nie pasuje żadna, nazwij go jak w polskich tabelach wartości odżywczych, ze stanem po przecinku (np. "Pierś z kurczaka, smażona"). Markę podaj w "brand" tylko wtedy, gdy jest czytelna na opakowaniu.
5. Dla każdej pozycji podaj typowe wartości na 100 g (lub 100 ml) w tym stanie: kcal, białko, tłuszcz, węglowodany (tabele IŻŻ / USDA). Jeśli na zdjęciu widać tabelę wartości odżywczych z etykiety – użyj jej.
6. "confidence": "high" – dobrze widać i łatwo ocenić, "medium" – zwykłe szacowanie, "low" – zgadujesz.

Format – dokładnie te klucze, liczby z kropką, bez komentarzy:
```json
{
  "homelist": 1,
  "meal": "Krótka nazwa posiłku",
  "items": [
    { "name": "Ryż biały, gotowany", "brand": null, "grams": 180, "count": null, "unit": null,
      "per100": { "kcal": 130, "protein": 2.7, "fat": 0.3, "carbs": 28.2 }, "confidence": "medium" },
    { "name": "Jajko kurze", "brand": null, "grams": 110, "count": 2, "unit": "sztuka",
      "per100": { "kcal": 143, "protein": 12.6, "fat": 9.5, "carbs": 0.7 }, "confidence": "high" }
  ],
  "note": "Jedno krótkie zdanie: co było najtrudniejsze do oszacowania."
}
```
{{NOTE}}
SŁOWNIK HOMELIST (produkty podstawowe):
TXT;
$prompt .= "\n" . implode('; ', $base) . "\n";
if ($frequent) $prompt .= "\nProdukty, które często jem (jeśli pasują, użyj tej nazwy i marki):\n" . implode('; ', $frequent) . "\n";

hl_food_json('success', '', ['prompt' => $prompt]);
