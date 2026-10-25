<?php if (!isset($acc)) exit; // część account.php ?>
<p class="<?=$acc_title?>">Dane osobowe</p>
<p class="<?=$acc_subtitle?>">Informacje widoczne dla domowników na wspólnych listach.</p>

<form action="scripts/users/edit_profile.php" method="POST" enctype="multipart/form-data" class="<?=$acc_card?>">
    <div class="flex items-center gap-4">
        <label for="photo_img" class="relative h-20 w-20 flex-shrink-0 hover:scale-105 hover:shadow-xl duration-150 active:scale-95 cursor-pointer rounded-full" title="Zmień zdjęcie">
            <img id="popup_img_inpt_photo_img" class="h-20 w-20 aspect-square rounded-full object-cover border border-black/10" src="img/users_images/<?=$acc_e($acc_img)?>" alt="">
            <span class="absolute -bottom-0.5 -right-0.5 rounded-full bg-green-400 p-1.5 text-white ring-2 ring-white dark:ring-[#2c2c2c]">
                <?=$acc_icon('m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125', 'size-3.5')?>
            </span>
        </label>
        <input onchange="imgPrevProduct('photo_img')" type="file" accept="image/*" name="photo_img" id="photo_img" class="hidden">
        <div class="min-w-0">
            <p class="font-medium text-lg text-black dark:text-gray-200 font-[poppins] truncate"><?=$acc_e(trim($acc['name'] . ' ' . $acc['sur_name']))?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Kliknij zdjęcie, aby je zmienić.</p>
        </div>
    </div>
    <div class="mt-5 grid sm:grid-cols-2 gap-3">
        <div>
            <label for="acc_name" class="<?=$acc_label?>">Imię / nazwa</label>
            <input name="name" id="acc_name" type="text" value="<?=$acc_e($acc['name'])?>" class="<?=$acc_input?>" required>
        </div>
        <div>
            <label for="acc_sur_name" class="<?=$acc_label?>">Nazwisko</label>
            <input name="sur_name" id="acc_sur_name" type="text" value="<?=$acc_e($acc['sur_name'])?>" class="<?=$acc_input?>">
        </div>
        <div class="sm:col-span-2">
            <label for="acc_email" class="<?=$acc_label?>">Email (login)</label>
            <input name="email" id="acc_email" type="email" value="<?=$acc_e($acc['mail'])?>" data-original="<?=$acc_e($acc['mail'])?>" oninput="document.getElementById('acc_email_confirm').classList.toggle('hidden', this.value.trim().toLowerCase() === this.dataset.original.toLowerCase())" class="<?=$acc_input?>" required>
        </div>
        <div id="acc_email_confirm" class="sm:col-span-2 hidden">
            <label for="acc_current_password" class="<?=$acc_label?>">Aktualne hasło (wymagane przy zmianie emaila)</label>
            <input name="current_password" id="acc_current_password" type="password" autocomplete="current-password" class="<?=$acc_input?>">
        </div>
        <div class="sm:col-span-2">
            <label for="acc_description" class="<?=$acc_label?>">Opis</label>
            <textarea name="description" id="acc_description" rows="2" placeholder="Nic tu nie ma..." class="<?=str_replace('rounded-xl', 'rounded-2xl', $acc_input)?> resize-none"><?=$acc_e($acc['description'])?></textarea>
        </div>
    </div>
    <div class="mt-5 flex justify-end">
        <button class="<?=$acc_btn?>">
            <?=$acc_icon('m4.5 12.75 6 6 9-13.5')?>
            Zapisz
        </button>
    </div>
</form>
