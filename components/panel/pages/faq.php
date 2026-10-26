<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['faq', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include "../../../scripts/conn_db.php";
$faq = [];
$result = mysqli_query($conn, "SELECT * FROM faq");
while ($row = mysqli_fetch_assoc($result)) $faq[] = $row;
?>
<section data-aos="fade-up" data-aos-delay="100" class="max-w-5xl mx-auto mb-28 md:mb-10">
    <div class="flex items-center justify-between gap-3 pt-2">
        <div class="min-w-0">
            <h1 class="hl-title">Często zadawane pytania</h1>
            <p class="hl-sub">Pytania i odpowiedzi widoczne na stronie głównej.</p>
        </div>
        <button type="button" onclick="openPopupFaqAdd()" title="Dodaj pytanie" class="<?=hl_can('faq', 'add') ? '' : 'hidden '?>hl-icon-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        </button>
    </div>

    <div class="hl-card mt-6 p-2">
        <div class="hl-divide">
            <?php if (!$faq): ?>
            <p class="py-10 text-center text-sm text-gray-400">Nie ma jeszcze żadnych pytań.</p>
            <?php endif; ?>
            <?php foreach ($faq as $row):
                // odpowiedź to HTML z edytora – w podglądzie sam tekst
                $answer = trim(html_entity_decode(strip_tags((string)$row['answer']), ENT_QUOTES, 'UTF-8'));
            ?>
            <div onclick="openPopupFaqEdit(<?=(int)$row['id']?>)" class="hl-row !items-start sm:grid sm:grid-cols-3 sm:gap-6">
                <span class="hl-tile !size-8 sm:hidden"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="!size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg></span>
                <div class="min-w-0 flex-1 sm:contents">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?=htmlspecialchars($row['question'], ENT_QUOTES, 'UTF-8')?></p>
                    <p class="mt-1 sm:mt-0 sm:col-span-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-3"><?=htmlspecialchars($answer, ENT_QUOTES, 'UTF-8')?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php 
$name_in_scripts = 'FaqEdit';
$delete_path = 'scripts/settings/faq/delete.php';
$delete_v2 = 'false';
$path = 'components/panel/faq/edit.php';
$close="";
include "../../popup.php";
?>

<?php 
$name_in_scripts = 'FaqAdd';
$delete_path = '';
$delete_v2 = 'true';
$path = 'components/panel/faq/add.php';
$close="";
include "../../popup.php";
?>