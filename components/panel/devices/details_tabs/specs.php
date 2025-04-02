<div class="divide-y divide-gray-200" data-aos="fade-in" data-aos-delay="100">
            <?php
            $id = $_GET['id'];
            include '../../../../scripts/conn_db.php';
            $sql = "SELECT rfid_gates.producent_id, rfid_gates.unique_gate_key, rfid_gates.mfg_date, rfid_gates.way_type_id, rfid_gates.os_version, gates_models.model, gates_models.frequency from rfid_gates join gates_models on gates_models.id=rfid_gates.model_id where rfid_gates.id=$id;";

            $result = mysqli_query($conn, $sql);
            if(mysqli_num_rows($result) > 0)
            {
                $row2 = mysqli_fetch_assoc($result);
            }
        
            ?>
            <div class=" text-sm grid grid-cols-4 py-2 even:bg-gray-100 odd:bg-white hover:bg-gray-200 duration-100">
                    <span></span>
                    <div class="font-medium">
                        Producent
                    </div>
                    <div class="pl-4 col-span-2 flex items-center gap-2 pr-2 border-l border-bray-200">
                        <span class="w-full px-2 focus:outline-violet-600"><?=$row2['producent_id']?></span>
                    </div>
            </div>
            <div class=" text-sm grid grid-cols-4 py-2 even:bg-gray-100 odd:bg-white hover:bg-gray-200 duration-100">
                    <span></span>
                    <div class="font-medium">
                        Model
                    </div>
                    <div class="pl-4 col-span-2 flex items-center gap-2 pr-2 border-l border-bray-200">
                        <span class="w-full px-2 focus:outline-violet-600"><?=$row2['model']?></span>
                    </div>
            </div>
            <div class=" text-sm grid grid-cols-4 py-2 even:bg-gray-100 odd:bg-white hover:bg-gray-200 duration-100">
                    <span></span>
                    <div class="font-medium">
                        Częstotliwość RFID
                    </div>
                    <div class="pl-4 col-span-2 flex items-center gap-2 pr-2 border-l border-bray-200">
                        <span class="w-full px-2 focus:outline-violet-600"><?=$row2['frequency']?></span>
                    </div>
            </div>
            <div class=" text-sm grid grid-cols-4 py-2 even:bg-gray-100 odd:bg-white hover:bg-gray-200 duration-100">
                    <span></span>
                    <div class="font-medium">
                        Wersja oprogramowania
                    </div>
                    <div class="pl-4 col-span-2 flex items-center gap-2 pr-2 border-l border-bray-200">
                        <span class="w-full px-2 focus:outline-violet-600"><?=$row2['os_version']?></span>
                    </div>
            </div>
            <div class=" text-sm grid grid-cols-4 py-2 even:bg-gray-100 odd:bg-white hover:bg-gray-200 duration-100">
                    <span></span>
                    <div class="font-medium">
                        Data produkcji
                    </div>
                    <div class="pl-4 col-span-2 flex items-center gap-2 pr-2 border-l border-bray-200">
                        <span class="w-full px-2 focus:outline-violet-600"><?=date_format(date_create($row2['mfg_date']), "d/m/Y")?></span>
                    </div>
            </div>
            <div class=" text-sm grid grid-cols-4 py-2 even:bg-gray-100 odd:bg-white hover:bg-gray-200 duration-100">
                    <span></span>
                    <div class="font-medium flex flex-row gap-2 items-center">
                        Tryb pracy 
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                    </div>
                    <div class="pl-4 col-span-2 flex items-center gap-2 pr-2 border-l border-bray-200">
                        <span class="w-full px-2 focus:outline-violet-600"><?=$row2['way_type_id']?></span>
                    </div>
            </div>
            <div class=" text-sm grid grid-cols-4 py-2 even:bg-gray-100 odd:bg-white hover:bg-gray-200 duration-100">
                    <span></span>
                    <div class="font-medium">
                        Klucz identyfikacyjny
                    </div>
                    <div class="pl-4 col-span-2 flex items-center gap-2 pr-2 border-l border-bray-200">
                        <span class="w-full px-2 focus:outline-violet-600"><?=$row2['unique_gate_key']?></span>
                    </div>
            </div>
</div>