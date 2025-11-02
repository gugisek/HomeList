<?php
include "scripts/conn_db.php";
$sql = "select count(*) as zadania from list_elements where status_id = 2";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$zadania = $row['zadania'];
$sql = "select count(*) as listy from lists";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$listy = $row['listy'];
$sql = "select count(*) as users from users";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$users = $row['users'];
$sql = "select count(*) as zaproszenia from list_invities";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$zaproszenia = $row['zaproszenia'];

?>
<div class="py-24 sm:py-32">
  <div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="mx-auto max-w-2xl lg:max-w-none">
      <div class="text-center">
        <h2 data-aos="fade-up" data-aos-delay="100" class="text-3xl font-bold tracking-tight text-gray-800 sm:text-4xl">Trochę naszych statystyk</h2>
        <p data-aos="fade-up" data-aos-delay="200" class="mt-4 text-lg leading-8 text-gray-600">Zobacz jak działamy, ile osób korzysta z naszej platformy.</p>
      </div>
      <dl class="mt-16 grid grid-cols-1 gap-[1px] overflow-hidden rounded-2xl text-center sm:grid-cols-2 lg:grid-cols-4">
        <div data-aos="fade-up" data-aos-delay="300" class="flex flex-col bg-black/70 p-8">
          <dt class="text-sm font-semibold leading-6 text-gray-200">Wykonanych zadań</dt>
          <dd class="order-first text-3xl font-semibold tracking-tight text-white"><?=$zadania?></dd>
        </div>
        <div data-aos="fade-up" data-aos-delay="400" class="flex flex-col bg-black/70 p-8">
          <dt class="text-sm font-semibold leading-6 text-gray-200">Stworzonych list</dt>
          <dd class="order-first text-3xl font-semibold tracking-tight text-white"><?=$listy?></dd>
        </div>
        <div data-aos="fade-up" data-aos-delay="500" class="flex flex-col bg-black/70 p-8">
          <dt class="text-sm font-semibold leading-6 text-gray-200">Aktywnych użytkowników</dt>
          <dd class="order-first text-3xl font-semibold tracking-tight text-white"><?=$users?></dd>
        </div>
        <div data-aos="fade-up" data-aos-delay="600" class="flex flex-col bg-black/70 p-8">
          <dt class="text-sm font-semibold leading-6 text-gray-200">Zaproszeń do list</dt>
          <dd class="order-first text-3xl font-semibold tracking-tight text-white"><?=$zaproszenia?></dd>
        </div>
      </dl>
    </div>
  </div>
</div>
