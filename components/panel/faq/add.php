
<div class="-mt-10 mb-4">
        <h1 class="text-lg font-semibold font-[poppins]">Dodaj pytanie FAQ</h1>
</div>
<form action="scripts/settings/faq/edit_faq.php" method="POST" class="text-white flex flex-col h-full gap-4 pt-2 px-2 font-[poppins]">
    <input type="hidden" name="id" value="add">

    <div class="flex flex-row gap-4 w-full">
        <div class="relative z-0 w-full">
            <input required type="text" id="question" name="question" class="block py-2.5 px-0 w-full text-sm bg-transparent border-0 border-b-[1px] border-gray-300 appearance-none text-gray-900 focus:text-black dark:border-gray-600 dark:focus:theme-border focus:outline-none focus:ring-0 theme-border-focus peer" value="" />
            <label for="question" class="absolute text-sm text-gray-900 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 theme-text-focus peer-focus:dark:text-[--text] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto">Pytanie</label>
        </div>
    </div>
        <div class="h-full max-h-[45vh] text-gray-900" id="editor-container-popup"></div>
        <input type="hidden" id="editorContent-faq" name="answer" value=''>
    <div class="mt-4 mb-2 sm:flex sm:flex-row-reverse">
        <button class="active:scale-95 duration-150 inline-flex w-full justify-center rounded-full bg-gray-900 duration-150 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500 sm:ml-2 sm:w-auto">Zapisz</button>
        <button onclick="popupFaqAddOpenClose()" type="button" class="active:scale-95 duration-150 mt-3 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto">Nie zapisuj</button>
        
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

