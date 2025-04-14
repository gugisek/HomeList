<div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8 mt-[8vh]">
  <div class="sm:mx-auto sm:w-full sm:max-w-md">
    <h2 class="mt-6 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">Logowanie</h2>
    <div class="flex sm:flex-row flex-col items-center justify-center mt-2 gap-2 px-2">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-green-500">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
      </svg>

      <span class="font-[poppins] text-sm text-center">
        HomeList - Twoja lista ToDo w Twoim domu.
      </span>  
    </div>
  </div>

  <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-[480px]">
    <div class="bg-white px-6 py-12 shadow sm:rounded-lg sm:px-12">
      <form class="space-y-6" action="scripts/login/login_script.php" method="POST">
        <div>
          <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Adres email</label>
          <div class="mt-2">
            <input id="email" name="email" type="email" autocomplete="email" required class="duration-150 px-4 block w-full rounded-xl border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 outline-green-400 duartion-150 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Hasło</label>
          <div class="mt-2">
            <input id="password" name="password" type="password" autocomplete="current-password" required class="duration-150 px-4 block w-full rounded-xl border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 outline-green-400 duartion-150 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 rounded duration-150">
            <label for="remember-me" class="ml-3 block text-sm leading-6 text-gray-900">Zapamiętaj dane</label>
          </div>

          <div class="text-sm leading-6">
            <a href="#" class="font-semibold text-gray-600 hover:text-green-300 duration-150">Nie pamiętam hasła</a>
          </div>
        </div>

        <div class="flex flex-col items-center justify-center">
          <button type="submit" class="flex w-full mb-3 items-center justify-center flex flex-row items-center justify-center gap-2 text-sm font-semibold leading-6 text-black hover:bg-green-100 px-8 py-2 bg-gray-100 rounded-xl hover:text-green-500 druation-150 active:scale-95 transition-all">Zaloguj</button>
          <a class="text-center text-sm">Logując <span class="text-green-400">zgadzasz</span> się na używanie plików cookie 🍪</a>
        </div>
      </form>

      <div>
      </div>
    </div>

    <p class="mt-10 text-center text-sm text-gray-500">
      Nie masz konta?
      <a href="#" class="font-semibold leading-6 text-gray-600 hover:text-green-300 duration-150">Skontaktuj się z administratorem</a>
    </p>
  </div>
</div>
