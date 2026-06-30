
<style>
.popup-center-hidden { transform: scale(0.92); opacity: 0; pointer-events: none; }
.popup-sheet { transition: transform 0.32s cubic-bezier(0.32, 0.72, 0, 1); }
.popup-sheet.popup-sheet-hidden { transform: translateY(110%); }
@media (min-width: 640px) {
  .popup-sheet.popup-sheet-hidden { transform: scale(0); }
}
</style>

<section id="popup<?=$name_in_scripts?>Bg" class="fixed backdrop-blur-md z-[50] h-0 opacity-0 top-0 left-0 w-full h-full bg-black/50 transition-opacity duration-300"></section>
<section id="popup<?=$name_in_scripts?>"
          <?php
          if(isset($close) and $close == 'true'){
            echo 'onclick="popup'.$name_in_scripts.'OpenClose()"';
          }else{
            echo 'onclick="popup'.$name_in_scripts.'CloseConfirm()"';
          }
          ?>
 class="popup-center-hidden z-[60] fixed top-0 left-0 w-full h-full transition-[transform,opacity] duration-200" style="opacity:0;pointer-events:none">
  <div class="flex items-center justify-center w-full h-full px-3">
    <div id="popup<?=$name_in_scripts?>Card" name="popup" onclick="event.cancelBubble=true;"
         class="relative bg-white dark:bg-[#1e1e1e]
                shadow-2xl shadow-black/20 dark:shadow-black/60
                ring-1 ring-black/[0.06] dark:ring-white/[0.06]
                rounded-[28px]
                min-w-[340px] max-w-[640px] w-full
                max-h-[85vh]
                flex flex-col">
                <!-- NO overflow-hidden na karcie — pozwala dropdown menu wychodzić poza granicę -->

      <!-- Close button (absolute, nie zabiera przestrzeni flow) -->
      <button
        <?php
        if(isset($close) and $close == 'true'){
          echo 'onclick="popup'.$name_in_scripts.'OpenClose()"';
        }else{
          echo 'onclick="popup'.$name_in_scripts.'CloseConfirm()"';
        }
        ?>
        type="button"
        class="absolute top-3 right-3 z-10 flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-white/10 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-white transition-colors duration-150 focus:outline-none focus:ring-2 ring-green-400">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Content — pt-14 (56px) absorbuje -mt-10/-mt-[43px] z plików treści i daje oddech -->
      <div id="popupItself" class="flex-1 overflow-y-auto rounded-[28px] pt-14 mt-[2px] px-5 sm:px-6 pb-6">
        <div id="pupup<?=$name_in_scripts?>Output">
          <div class='w-full flex items-center justify-center py-10'>
            <div class='lds-dual-ring'></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Close confirmation dialog — bottom sheet na mobilce, center na desktop -->
<section id="popup<?=$name_in_scripts?>CloseBg" class="fixed z-[65] backdrop-blur-md h-0 opacity-0 top-0 left-0 w-full h-full bg-black/50 transition-opacity duration-300"></section>
<section id="popup<?=$name_in_scripts?>Close" onclick="popup<?=$name_in_scripts?>CloseConfirm()" class="popup-sheet popup-sheet-hidden z-[70] fixed top-0 left-0 w-full h-full font-[poppins]" style="opacity:0;pointer-events:none">
  <div class="flex flex-col sm:items-center sm:justify-center items-stretch justify-end w-full h-full">
    <div onclick="event.cancelBubble=true;"
         class="bg-white dark:bg-[#1e1e1e] shadow-2xl shadow-black/20 dark:shadow-black/60
                ring-1 ring-black/[0.06] dark:ring-white/[0.06]
                sm:rounded-[28px] rounded-t-[32px] sm:max-w-sm sm:w-auto w-full overflow-hidden">
      <div class="sm:hidden flex justify-center pt-3 pb-0">
        <div class="w-9 h-1 rounded-full bg-gray-300 dark:bg-white/20"></div>
      </div>
      <div class="px-5 pt-5 pb-2 flex items-start gap-4">
        <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-400/10">
          <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Niezapisane zmiany</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Czy na pewno chcesz wyjść? Zmiany nie zostaną zapisane.</p>
        </div>
      </div>
      <div class="px-5 pb-6 pt-4 flex flex-col sm:flex-row-reverse gap-2 sm:gap-3">
        <button onclick="popup<?=$name_in_scripts?>CloseConfirm()" type="button"
                class="w-full sm:w-auto active:scale-95 inline-flex justify-center rounded-xl bg-[#1c1c1e] dark:bg-white/10 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-gray-700 dark:hover:bg-white/20 transition-colors duration-150">
          Zostań
        </button>
        <button onclick="popup<?=$name_in_scripts?>OpenClose();popup<?=$name_in_scripts?>CloseConfirm()" type="button"
                class="w-full sm:w-auto active:scale-95 inline-flex justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-150">
          Nie zapisuj
        </button>
      </div>
    </div>
  </div>
</section>

<!-- Delete confirmation dialog — bottom sheet na mobilce, center na desktop -->
<section id="popup<?=$name_in_scripts?>DeleteBg" class="fixed z-[65] backdrop-blur-md h-0 opacity-0 top-0 left-0 w-full h-full bg-black/50 transition-opacity duration-300"></section>
<section id="popup<?=$name_in_scripts?>Delete" onclick="popup<?=$name_in_scripts?>Delete()" class="popup-sheet popup-sheet-hidden z-[70] fixed top-0 left-0 w-full h-full font-[poppins]" style="opacity:0;pointer-events:none">
  <div class="flex flex-col sm:items-center sm:justify-center items-stretch justify-end w-full h-full">
    <div onclick="event.cancelBubble=true;"
         class="bg-white dark:bg-[#1e1e1e] shadow-2xl shadow-black/20 dark:shadow-black/60
                ring-1 ring-black/[0.06] dark:ring-white/[0.06]
                sm:rounded-[28px] rounded-t-[32px] sm:max-w-sm sm:w-auto w-full overflow-hidden">
      <div class="sm:hidden flex justify-center pt-3 pb-0">
        <div class="w-9 h-1 rounded-full bg-gray-300 dark:bg-white/20"></div>
      </div>
      <div class="px-5 pt-5 pb-2 flex items-start gap-4">
        <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-red-50 dark:bg-red-400/10">
          <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Usuń rekord</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Tej operacji nie można cofnąć. Rekord zostanie trwale usunięty.</p>
        </div>
      </div>
      <form action="<?=$delete_path?>" method="POST" class="px-5 pb-6 pt-4 flex flex-col sm:flex-row-reverse gap-2 sm:gap-3">
        <input type="hidden" name="id" id="id_for_delete_<?=$name_in_scripts?>" value="">
        <?php
        if($delete_v2 == 'true'){
          echo '
            <a onclick="'.$name_in_scripts.'Delete()" class="w-full sm:w-auto active:scale-95 cursor-pointer inline-flex justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 shadow-sm transition-colors duration-150">Usuń</a>
            <a onclick="popup'.$name_in_scripts.'Delete()" type="button" class="w-full sm:w-auto cursor-pointer active:scale-95 inline-flex justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-150">Anuluj</a>
          ';
        }else{
          echo '
            <button class="w-full sm:w-auto active:scale-95 inline-flex justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 shadow-sm transition-colors duration-150">Usuń</button>
            <button onclick="popup'.$name_in_scripts.'Delete()" type="button" class="w-full sm:w-auto active:scale-95 inline-flex justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-150">Anuluj</button>
          ';
        }
        ?>
      </form>
    </div>
  </div>
</section>

<script>
  function popup<?=$name_in_scripts?>OpenClose() {
    var popup = document.getElementById('popup<?=$name_in_scripts?>');
    var popupBg = document.getElementById('popup<?=$name_in_scripts?>Bg');
    popup.style.opacity = ''; popup.style.pointerEvents = '';
    popupBg.classList.toggle('opacity-0');
    popupBg.classList.toggle('h-0');
    popup.classList.toggle('popup-center-hidden');
    clearInterval(window.popupInterval);
  }
  function openPopup<?=$name_in_scripts?>(id) {
    var id_for_delete = document.getElementById('id_for_delete_<?=$name_in_scripts?>');
    if (id_for_delete) id_for_delete.value = id;
    var popupOutput = document.getElementById('pupup<?=$name_in_scripts?>Output');
    popupOutput.innerHTML = "<div class='w-full flex items-center justify-center py-10'><div class='lds-dual-ring'></div></div>";
    popup<?=$name_in_scripts?>OpenClose();
    fetch('<?=$path?>?id=' + id)
      .then(r => r.text())
      .then(data => {
        var parser = new DOMParser();
        var doc = parser.parseFromString(data, 'text/html');
        popupOutput.innerHTML = doc.body.innerHTML;
        document.querySelectorAll('.popup-script').forEach(s => s.remove());
        doc.querySelectorAll('script').forEach(s => {
          var el = document.createElement('script');
          el.textContent = s.textContent;
          el.classList.add('popup-script');
          document.body.appendChild(el);
        });
      });
  }
</script>
<script>
  function popup<?=$name_in_scripts?>CloseConfirm() {
    var popup = document.getElementById('popup<?=$name_in_scripts?>Close');
    var popupBg = document.getElementById('popup<?=$name_in_scripts?>CloseBg');
    popup.style.opacity = ''; popup.style.pointerEvents = '';
    popupBg.classList.toggle('opacity-0');
    popupBg.classList.toggle('h-0');
    popup.classList.toggle('popup-sheet-hidden');
  }
</script>
<script>
  function popup<?=$name_in_scripts?>Delete() {
    var popup = document.getElementById('popup<?=$name_in_scripts?>Delete');
    var popupBg = document.getElementById('popup<?=$name_in_scripts?>DeleteBg');
    popup.style.opacity = ''; popup.style.pointerEvents = '';
    popupBg.classList.toggle('opacity-0');
    popupBg.classList.toggle('h-0');
    popup.classList.toggle('popup-sheet-hidden');
  }
</script>
