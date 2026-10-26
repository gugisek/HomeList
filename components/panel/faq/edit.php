<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['faq', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
$id = (int)($_GET['id'] ?? 0);

    include "../../../scripts/conn_db.php";

    $stmt = $conn->prepare("SELECT * FROM faq where id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        echo '<p class="text-sm text-gray-500">Nie znaleziono pytania.</p>';
        exit;
    }
    // answer to oczyszczony HTML z edytora (scripts/settings/faq/edit_faq.php); question – zwykły tekst
    $row['question'] = htmlspecialchars($row['question'], ENT_QUOTES, 'UTF-8');

?>
<div class="-mt-10 mb-4">
        <h1 class="hl-popup-title">Edytuj pytanie FAQ</h1>
</div>
<form action="scripts/settings/faq/edit_faq.php" method="POST" class="flex flex-col h-full gap-4 pt-2 font-[poppins]">
    <input type="hidden" name="id" value="<?=$row['id']?>">
    <input type="hidden" name="type" value="<?=$type?>">
    <div>
        <label for="question" class="hl-label">Pytanie</label>
        <input required type="text" id="question" name="question" class="hl-input" value="<?=$row['question']?>" />
    </div>
        <div class="h-full max-h-[45vh] text-gray-900 dark:text-gray-200 rounded-b-2xl" id="editor-container-popup"><?=$row['answer']?></div>
        <input type="hidden" id="editorContent-faq" name="answer" value="<?=htmlspecialchars($row['answer'], ENT_QUOTES, 'UTF-8')?>">
    <div class="mt-2 flex flex-row-reverse items-center gap-2">
        <button class="hl-btn hl-btn-primary flex-1">Zapisz</button>
        <button onclick="popupFaqEditOpenClose()" type="button" class="hl-btn hl-btn-ghost flex-1">Nie zapisuj</button>
        <button type="button" onclick="popupFaqEditDelete()" title="Usuń" class="hl-btn hl-btn-danger hl-btn-square"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button>
    </div>
</form>


<script>
  var quill = new Quill('#editor-container-popup', {
    theme: 'snow',
    placeholder: 'Tu wpisz treść...',
    modules: {
      toolbar: [
        [{ 'size': [ 'small', false, 'large', 'huge'] }],
        ['bold', 'italic', 'underline', 'strike'],  // Funkcje pogrubiania, kursywy, podkreślenia, przekreślenia
        // Dodaj niestandardową paletę kolorów
        ['link'],
        ['blockquote'],
        ['code'],
        [{ 'color': [false, 'var(--text)', '#ffffff', 'rgb(243 244 246)', 'rgb(229 231 235)', 'rgb(209 213 219)', 'rgb(156 163 175)', 'rgb(107 114 128)', 'rgb(75 85 99)', 'rgb(55 65 81)', 'rgb(31 41 55)', 'rgb(17 24 39)', 'rgb(3 7 18)', 'black'] }],
        // Inne opcje
        
      ],
    },
  });


  // Dodaj event listener do śledzenia zmian w treści
  quill.on('text-change', function(delta, oldDelta, source) {
    // Zaktualizuj ukryte pole lub wykonaj inne operacje po zmianie treści
    updateHiddenField();
  });

  // Funkcja aktualizująca ukryte pole
  function updateHiddenField() {
    var editorContent = document.getElementById('editorContent-faq');
    editorContent.value = quill.root.innerHTML;
  }

</script>

