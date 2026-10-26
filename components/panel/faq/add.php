<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['faq', 'add']; include __DIR__.'/../../../scripts/security.php'; ?>

<div class="-mt-10 mb-4">
        <h1 class="hl-popup-title">Dodaj pytanie FAQ</h1>
</div>
<form action="scripts/settings/faq/edit_faq.php" method="POST" class="flex flex-col h-full gap-4 pt-2 font-[poppins]">
    <input type="hidden" name="id" value="add">

    <div>
        <label for="question" class="hl-label">Pytanie</label>
        <input required type="text" id="question" name="question" class="hl-input" value="" />
    </div>
        <div class="h-full max-h-[45vh] text-gray-900 dark:text-gray-200 rounded-b-2xl" id="editor-container-popup"></div>
        <input type="hidden" id="editorContent-faq" name="answer" value=''>
    <div class="mt-2 flex flex-row-reverse items-center gap-2">
        <button class="hl-btn hl-btn-primary flex-1">Zapisz</button>
        <button onclick="popupFaqAddOpenClose()" type="button" class="hl-btn hl-btn-ghost flex-1">Nie zapisuj</button>
        
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

