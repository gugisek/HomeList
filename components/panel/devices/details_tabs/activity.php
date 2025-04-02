<div data-aos="fade-in" data-aos-delay="100" class="flex items-center w-full justify-center">
                <!-- <span class="text-sm text-gray-600">Status:</span> -->
                <div class="flex items-center gap-x-3 border border-red-500/50 rounded-full px-3 py-1">
                    <div class="flex-none rounded-full p-1 text-red-500 bg-red-200/50">
                        <div class="h-2 w-2 rounded-full bg-current"></div>
                    </div>
                <h2 class="min-w-0 text-sm font-semibold leading-6">
                    <a href="#" class="flex gap-x-2">
                    <span class="truncate">Live data</span>
                    <!-- <span class="text-gray-400">/</span>
                    <span class="whitespace-nowrap">dział</span> -->
                    
                    </a>
                </h2>
                </div>
</div>
<div class="px-4 sm:px-6 lg:px-8">
  <div class="flow-root">
    <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
      <div class="inline-block min-w-full py-2 align-middle">
        <table class="min-w-full border-separate border-spacing-0">
          <thead>
            <tr>
              <th scope="col" class="sticky top-0 z-10 border-b border-gray-300 bg-white bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter sm:pl-6 lg:pl-8">Użytkownik</th>
              <th scope="col" class="sticky top-0 z-10 hidden border-b border-gray-300 bg-white bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter sm:table-cell">Tag</th>
              <th scope="col" class="sticky top-0 z-10 border-b border-gray-300 bg-white bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter">Data</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $id = $_GET['id'];
            include '../../../../scripts/conn_db.php';
            $sql = "SELECT rfid_actions.tag, rfid_actions.date, users.name, users.sur_name from rfid_actions join users on users.rfid_tag=rfid_actions.tag where gate_id = $id";
            $result = mysqli_query($conn, $sql);
                if(mysqli_num_rows($result) > 0)
                {
                    //window.location=`?page=użytkownicy&action=edit&id='.$row['id'].'#edit`;
                    while($row = mysqli_fetch_assoc($result))
                    {
                        echo '
                        <tr>
                            <td class="whitespace-nowrap border-b border-gray-200 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8">'.$row['name'].' '.$row['sur_name'].'</td>
                            <td class="whitespace-nowrap border-b border-gray-200 hidden px-3 py-4 text-sm text-gray-500 sm:table-cell">'.$row['tag'].'</td>
                            <td class="whitespace-nowrap border-b border-gray-200 px-3 py-4 text-sm text-gray-500">'.date_format(date_create($row['date']),"H:i:s d/m/Y").'</td>
                        </tr>
                        ';
                    }
                }else{
                    echo '
                    <span class="text-xs text-gray-600">Brak akcji dla tej bramki</span>
                    ';
                }
            ?>
            

            <!-- More people... -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>