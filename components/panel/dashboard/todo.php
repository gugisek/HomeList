<section id="dashboard_body" data-aos="fade-up" data-aos-delay="100">
    <div class="flex items-center justify-between">
        <div class="text-gray-400">
            <span class="font-medium text-2xl text-black font-[poppins]">ToDo</span>
        </div>
        <div onclick="openPopupElementAdd('todo')"  class="hover:text-white hover:bg-green-400 hover:shadow-xl shadow-green-300 hover:scale-105 active:scale-90 duration-150 group flex gap-x-3 rounded-xl p-3 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
    </div>
</section>
<section class="mt-2" data-aos="fade-up" data-aos-delay="150">
<ul role="list" class="divide-y divide-white/5">
    <?php
    include '../../../scripts/conn_db.php';
    $sql = "SELECT * FROM `list_elements` ORDER BY `id` DESC";
    $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0)
             {
                while($row = mysqli_fetch_assoc($result))
                    {
                        echo '
                            <li class="relative flex items-center space-x-4 py-4">
                                <div class="min-w-0 flex-auto">
                                <div class="flex items-center gap-x-3">
                                    <div class="flex-none rounded-full p-1 text-gray-500 bg-gray-100/10">
                                    <div class="h-2 w-2 rounded-full bg-current"></div>
                                    </div>
                                    <h2 class="min-w-0 text-sm font-semibold leading-6">
                                    <a href="#" class="flex gap-x-2">
                                        <span class="truncate">'.$row['title'].'</span>
                                    </a>
                                    </h2>
                                </div>
                                <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
                                    <p class="truncate">Deploys from GitHub</p>
                                    <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
                                    <circle cx="1" cy="1" r="1" />
                                    </svg>
                                    <p class="whitespace-nowrap">Dodano '.date_format(date_create($row['create_date']), "H:i d.m").'</p>
                                </div>
                                </div>
                                <div class="rounded-full flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-400 bg-gray-400/10 ring-gray-400/20">Oczekuje</div>
                                <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </li>
                        ';
                    }
                }
                else {
                    echo '
                        <li>Brak zadań do wykonania</li>
                    ';
                }
    ?>
    </ul>
<!-- 
  <li class="relative flex items-center space-x-4 py-4">
    <div class="min-w-0 flex-auto">
      <div class="flex items-center gap-x-3">
        <div class="flex-none rounded-full p-1 text-green-400 bg-green-400/10">
          <div class="h-2 w-2 rounded-full bg-current"></div>
        </div>
        <h2 class="min-w-0 text-sm font-semibold leading-6 text-white">
          <a href="#" class="flex gap-x-2">
            <span class="truncate">Planetaria</span>
            <span class="text-gray-400">/</span>
            <span class="whitespace-nowrap">mobile-api</span>
            <span class="absolute inset-0"></span>
          </a>
        </h2>
      </div>
      <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
        <p class="truncate">Deploys from GitHub</p>
        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
          <circle cx="1" cy="1" r="1" />
        </svg>
        <p class="whitespace-nowrap">Deployed 3m ago</p>
      </div>
    </div>
    <div class="rounded-full flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset text-indigo-400 bg-indigo-400/10 ring-indigo-400/30">Production</div>
    <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
    </svg>
  </li>
  <li class="relative flex items-center space-x-4 py-4">
    <div class="min-w-0 flex-auto">
      <div class="flex items-center gap-x-3">
        <div class="flex-none rounded-full p-1 text-gray-500 bg-gray-100/10">
          <div class="h-2 w-2 rounded-full bg-current"></div>
        </div>
        <h2 class="min-w-0 text-sm font-semibold leading-6 text-white">
          <a href="#" class="flex gap-x-2">
            <span class="truncate">Tailwind Labs</span>
            <span class="text-gray-400">/</span>
            <span class="whitespace-nowrap">tailwindcss.com</span>
            <span class="absolute inset-0"></span>
          </a>
        </h2>
      </div>
      <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
        <p class="truncate">Deploys from GitHub</p>
        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
          <circle cx="1" cy="1" r="1" />
        </svg>
        <p class="whitespace-nowrap">Deployed 3h ago</p>
      </div>
    </div>
    <div class="rounded-full flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-400 bg-gray-400/10 ring-gray-400/20">Preview</div>
    <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
    </svg>
  </li>
  <li class="relative flex items-center space-x-4 py-4">
    <div class="min-w-0 flex-auto">
      <div class="flex items-center gap-x-3">
        <div class="flex-none rounded-full p-1 text-rose-400 bg-rose-400/10">
          <div class="h-2 w-2 rounded-full bg-current"></div>
        </div>
        <h2 class="min-w-0 text-sm font-semibold leading-6 text-white">
          <a href="#" class="flex gap-x-2">
            <span class="truncate">Protocol</span>
            <span class="text-gray-400">/</span>
            <span class="whitespace-nowrap">api.protocol.chat</span>
            <span class="absolute inset-0"></span>
          </a>
        </h2>
      </div>
      <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
        <p class="truncate">Deploys from GitHub</p>
        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
          <circle cx="1" cy="1" r="1" />
        </svg>
        <p class="whitespace-nowrap">Failed to deploy 6d ago</p>
      </div>
    </div>
    <div class="rounded-full flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-400 bg-gray-400/10 ring-gray-400/20">Preview</div>
    <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
    </svg>
  </li>
</ul> -->

</section>