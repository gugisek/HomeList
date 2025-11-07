<header class="absolute inset-x-0 top-0 z-50">
    <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
      <div class="flex lg:flex-1">
        <a href="/" class="-m-1.5 p-1.5 px-4">
          <img data-aos="fade-down" data-aos-delay="100" class="h-8 w-auto" src="img/icon_512.png" alt="">
        </a>
      </div>
      <div class="flex lg:hidden">
        <button onclick="openNavToggle()" type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-400">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>
      </div>
      <div class="hidden lg:flex lg:gap-x-12">
        <a data-aos="fade-down" data-aos-delay="200" href="#produkt" class="flex items-center justify-center"><span class="text-sm font-semibold leading-6 text-gray-800 hover:bg-green-400/30 px-4 py-1 rounded-lg hover:text-green-600 druation-150 active:scale-95 transition-all">Produkt</span></a>
        <a data-aos="fade-down" data-aos-delay="300" href="#oferta" class="flex items-center justify-center"><span class="text-sm font-semibold leading-6 text-gray-800 hover:bg-green-400/30 px-4 py-1 rounded-lg hover:text-green-600 duration-150 active:scale-95 transition-all">Oferta</span></a>
        <a data-aos="fade-down" data-aos-delay="400" href="#pytania" class="flex items-center justify-center"><span class="text-sm font-semibold leading-6 text-gray-800 hover:bg-green-400/30 px-4 py-1 rounded-lg hover:text-green-600 duration-150 active:scale-95 transition-all">Pytania</span></a>
        <a data-aos="fade-down" data-aos-delay="500" href="#kontakt" class="flex items-center justify-center"><span class="text-sm font-semibold leading-6 text-gray-800 hover:bg-green-400/30 px-4 py-1 rounded-lg hover:text-green-600 duration-150 active:scale-95 transition-all">Kontakt</span></a>
      </div>
      <div data-aos="fade-down" data-aos-delay="600" class="hidden lg:flex lg:flex-1 lg:justify-end">
        <a href="login.php" class="text-sm font-semibold leading-6 text-gray-800 hover:bg-green-400/30 px-4 py-1 rounded-lg hover:text-green-600 duration-150 active:scale-95 transition-all">Zaloguj się <span aria-hidden="true">&rarr;</span></a>
      </div>
    </nav>
    <!-- Mobile menu, show/hide based on menu open state. -->
    <div class="lg:hidden" role="dialog" aria-modal="true">
      <!-- Background backdrop, show/hide based on slide-over state. -->
      <!-- <div class="fixed inset-0 z-50"></div> -->
      <div id="sidenav_mobile" class="right-[-100%] transition-all duration-150 fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-[#fdfdfd] px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
        <div class="flex items-center justify-between">
          <a href="/" class="-m-1.5 p-1.5 px-4 flex items-center gap-2">
            <img  class="h-8 w-auto" src="img/icon_512.png" alt=""><span class="font-[poppins]">HomeList</span>
          </a>
          <button onclick="openNavToggle()" type="button" class="-m-2.5 rounded-md p-2.5 text-gray-400">
            <span class="sr-only">Close menu</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="mt-6 flow-root">
          <div class="-my-6 divide-y divide-gray-500/25">
            <div class="space-y-2 py-6">
              <a onclick="openNavToggle()" href="#produkt" class="-mx-3 block rounded-2xl px-3 py-2 text-base font-semibold leading-7 text-gray-800 hover:bg-green-300/30 active:scale-95 transition-all duration-150">Produkt</a>
              <a onclick="openNavToggle()" href="#oferta" class="-mx-3 block rounded-2xl px-3 py-2 text-base font-semibold leading-7 text-gray-800 hover:bg-green-300/30 active:scale-95 transition-all duration-150">Oferta</a>
              <a onclick="openNavToggle()" href="#pytania" class="-mx-3 block rounded-2xl px-3 py-2 text-base font-semibold leading-7 text-gray-800 hover:bg-green-300/30 active:scale-95 transition-all duration-150">Pytania</a>
              <a onclick="openNavToggle()" href="#kontakt" class="-mx-3 block rounded-2xl px-3 py-2 text-base font-semibold leading-7 text-gray-800 hover:bg-green-300/30 active:scale-95 transition-all duration-150">Kontakt</a>
            </div>
            <div class="py-6">
              <a href="login.php" class="-mx-3 block rounded-2xl active:scale-95 duration-150 px-3 py-2.5 text-base font-semibold leading-7 text-gray-800 hover:bg-green-300/30">Zaloguj się</a>
            </div>
          </div>
          <img src="img/logo2.png" alt="" class="mb-14">
  
                <div class="mx-auto max-w-7xl overflow-hidden">
                    <p class="mt-10 text-center text-xs leading-5 text-gray-500">2025 HomeList <?php include 'version.php'?> - designed and build by <a href="https://github.com/gugisek" target="_blank" class="text-gray-800 hover:text-blue-600 duration-150">gugisek</a> <br/>
                    powered by <a href="https://rgbpc.pl/" target="_blank" class="theme-text-hover duration-300"><span class="text-red-600">R</span><span class="text-green-600">G</span><span class="text-blue-600">B</span>pc.pl</a>
                    </p>
                </div>
   
        </div>
      </div>
    </div>
  </header>
  <script>
function openNavToggle() {
  const backdrop = document.getElementById('backdrop');
  const sidenav = document.getElementById('sidenav_mobile');
  sidenav.classList.toggle('right-[-100%]');
  sidebar.classList.toggle('right-0');
}
</script>