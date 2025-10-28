<div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8 mt-[8vh]">
  <div class="sm:mx-auto sm:w-full sm:max-w-md">
    <h2 class="mt-6 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">Rejestrowanie</h2>
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
      <form class="space-y-6" action="scripts/login/register_script.php" method="POST">
        <div>
          <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Nazwa Twojego konta</label>
          <div class="mt-2">
            <input id="name" name="name" type="text" autocomplete="name" required class="duration-150 px-4 block w-full rounded-xl border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 outline-green-400 duartion-150 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
          </div>
        </div>
        <div>
          <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Adres email</label>
          <div class="mt-2">
            <input id="email" name="email" type="email" autocomplete="email" required class="duration-150 px-4 block w-full rounded-xl border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 outline-green-400 duartion-150 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Hasło</label>
          <div class="mt-2 relative">
                    <input
                      name="password"
                      id="password"
                      type="password"
                      value=""
                      placeholder=""
                      class="duration-150 px-4 block w-full rounded-xl border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 outline-green-400 duartion-150 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                      required
                    >
                    <button
                      type="button"
                      onclick="toggleAuthToken('password')"
                      class="absolute inset-y-0 right-2 flex items-center text-gray-500 hover:text-gray-800"
                    >
                      👁️
                    </button>
        </div>
        </div>

        <div>
          <label for="re_password" class="block text-sm font-medium leading-6 text-gray-900">Powtórz hasło</label>
          <div class="mt-2 relative">
                    <input
                      name="re_password"
                      id="re_password"
                      type="password"
                      value=""
                      placeholder=""
                      class="duration-150 px-4 block w-full rounded-xl border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 outline-green-400 duartion-150 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                      required
                    >
                    <button
                      type="button"
                      onclick="toggleAuthToken('re_password')"
                      class="absolute inset-y-0 right-2 flex items-center text-gray-500 hover:text-gray-800"
                    >
                      👁️
                    </button>
        </div>
        </div>
        

        <div class="flex flex-col items-center justify-center">
          <a onclick="checkForm()" class="cursor-pointer flex w-full mb-3 items-center justify-center flex flex-row items-center justify-center gap-2 text-sm font-semibold leading-6 text-black hover:bg-green-100 px-8 py-2 bg-gray-100 rounded-xl hover:text-green-500 druation-150 active:scale-95 transition-all">Zarejestruj</a>
          <a class="text-center text-sm">Rejestrując się <span class="text-green-400">zgadzasz</span> się na używanie plików cookie 🍪</a>
        </div>
      </form>

      <div>
      </div>
    </div>

    <p class="mt-10 text-center text-sm text-gray-500">
      Masz konto?
      <a href="login.php" class="font-semibold leading-6 text-green-400 hover:text-green-300 duration-150">Zaloguj się ❤️</a>
    </p>
  </div>
</div>


<script>
  function toggleAuthToken(id) {
    const input = document.getElementById(id);
    const btn = event.currentTarget; // odwołanie do przycisku

    if (input.type === "password") {
      input.type = "text";
      btn.textContent = "🙈";
    } else {
      input.type = "password";
      btn.textContent = "👁️";
    }
  }

  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
  }

  function checkForm() {
    const password = document.getElementById('password').value;
    const re_password = document.getElementById('re_password').value;
    const email = document.getElementById('email').value;
    const name = document.getElementById('name').value;

    if (password != '' && re_password != '' && email != '' && name != '') {
      if (password !== re_password) {
        showAlert('warning', 'Hasła nie są identyczne!');
        return;
      }

      if (!validateEmail(email)) {
        showAlert('warning', 'Nieprawidłowy adres e-mail!');
        return;
      }
    }else{
      showAlert('warning', 'Wszystkie pola muszą być wypełnione!');
      return;
    }

    

    // Jeśli hasła są identyczne, prześlij formularz
    document.querySelector('form').submit();
  }


</script>