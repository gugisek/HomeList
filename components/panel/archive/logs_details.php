<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['logs', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
$id = (int)($_GET['id'] ?? 0);
include "../../../scripts/conn_db.php";
$stmt = $conn->prepare("SELECT logs.id, users.name, users.sur_name, logs.when, logs.object_id, logs.object_type, log_types.type,logs.description, logs.before, logs.after FROM `logs` left join users on users.id=logs.user_id join log_types on log_types.id=logs.type where logs.id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    echo '<p class="text-sm text-gray-500">Nie znaleziono wpisu.</p>';
    exit;
}
// before/after to tekst z danymi użytkowników rozdzielany <br/> – escapujemy całość i przywracamy tylko łamanie linii
$log_html = fn($s) => preg_replace('~&lt;br\s*/?&gt;~i', '<br/>', htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'));
$row['name'] = htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8');
$row['sur_name'] = htmlspecialchars((string)$row['sur_name'], ENT_QUOTES, 'UTF-8');
$when = $row['when'];
$objectId = (int)$row['object_id'];
$objectType = htmlspecialchars((string)$row['object_type'], ENT_QUOTES, 'UTF-8');
$before = $log_html($row['before']);
$after = $log_html($row['after']);
$type = $row['type'];
$description = htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8');

?>
   <section class="w-full rounded-3xl md:px-0 px-4 pb-4">
        <div class="-mt-4 mb-4">
        <h1 class="text-md font-bold">Szczegóły operacji</h1>
        <p class=" text-xs text-gray-500">Przeglądasz szczegóły rekordu.</p>
    </div>
        <div class="flex flex-col md:flex-row md:space-x-4">
            <div class="flex flex-col w-full md:w-1/2">
                <div class="flex flex-col md:flex-row gap-2 md:mr-2">
                    <div class="flex flex-col w-full md:w-1/2">
                        <div class="flex flex-col">
                            <label for="user_id" class="pb-2 pt-2 font-medium text-xs text-[#5d5d5d]">Użytkownik</label>
                            <input type="text" name="user_id" id="user_id" value="<?php echo $row['name'] . ' ' . $row['sur_name']; ?>" class="text-sm border border-gray-400 text-gray-900 rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500" disabled>
                        </div>
                    </div>
                    <div class="flex flex-col w-full md:w-1/2">
                        <div class="flex flex-col">
                            <label for="when" class="pb-2 pt-2 font-medium text-xs text-[#5d5d5d]">Kiedy</label>
                            <input type="text" name="when" id="when" value="<?php echo $when; ?>" class="text-sm border border-gray-400 text-gray-900 rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500" disabled>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col">
                    <label for="object_id" class="pb-2 font-medium pt-2 text-xs text-[#5d5d5d]">Id obiektu</label>
                    <input type="text" name="object_id" id="object_id" value="<?php echo $objectId; ?>" class="text-sm border border-gray-400 text-gray-900 rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500" disabled>
                </div>
                <div class="flex flex-col">
                    <label for="object_type" class="pb-2 pt-2 font-medium text-xs text-[#5d5d5d]">Typ obiektu</label>
                    <input type="text" name="object_type" id="object_type" value="<?php echo $objectType; ?>" class="text-sm border border-gray-400 text-gray-900 rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all
                    duration-500" disabled>
                </div>
                <div class="flex flex-col h-full">
                    <label for="before" class="pb-2 pt-2 font-medium text-xs text-[#5d5d5d]">Przed</label>
                    <div name="before" id="before" cols="30" rows="5" class="break-words h-full text-sm border border-gray-400 text-gray-900 rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500" disabled><?php echo $before; ?></div>
                </div>
            </div>
            <div class="flex flex-col w-full md:w-1/2">
                <div class="flex flex-col">
                    <label for="type" class="pb-2 font-medium text-xs pt-2 text-[#5d5d5d]">Nazwa obiektu</label>
                    <input type="text" name="type" id="type" value="<?php 
                    if ($objectType == "users") {$sql = "SELECT name, sur_name FROM `users` where id='$objectId';"; 
                        $result = mysqli_query($conn, $sql);
                        $row = mysqli_fetch_assoc($result);
                        echo htmlspecialchars($row['name'] . ' ' . $row['sur_name']);
                    } elseif ($objectType == "docs") {$sql = "SELECT name FROM `informations` where id='$objectId';"; 
                        $result = mysqli_query($conn, $sql);
                        $row = mysqli_fetch_assoc($result);
                        echo htmlspecialchars((string)$row['name']);
                    } elseif ($objectType == "faq" and $objectId != 0 and $type != 'delete') {$sql = "SELECT question FROM `faq` where id='$objectId';"; 
                        $result = mysqli_query($conn, $sql);
                        $row = mysqli_fetch_assoc($result);
                        echo htmlspecialchars((string)$row['question']);
                    } elseif ($objectType == "teams" and $objectId != 0 and $type != 'delete') {$sql = "SELECT name FROM `teams` where team_id='$objectId';"; 
                        $result = mysqli_query($conn, $sql);
                        $row = mysqli_fetch_assoc($result);
                        echo htmlspecialchars((string)$row['name']);
                    }

                    ?>" class="border border-gray-400 rounded-xl text-gray-900 px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500 text-sm" disabled>
                </div>
                <div class="flex flex-col">
                    <label for="type" class="pb-2 text-xs pt-2 font-medium text-[#5d5d5d]">Typ operacji</label>
                    <input type="text" name="type" id="type" value="<?php echo $type; ?>" class="
                    <?php if ($type == "create") {echo "bg-green-200 text-green-900";} elseif ($type == "delete") {echo "bg-red-200 text-red-900";} elseif ($type == "modify") {echo "bg-indigo-200 text-indigo-900";} ?>
                    border border-gray-400 font-semibold capitalize rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500 text-sm" disabled>
                </div>
                <div class="flex flex-col">
                    <label for="description" class="pb-2 text-xs pt-2 font-medium text-[#5d5d5d]">Opis</label>
                    <textarea name="description" id="description" cols="30" rows="1" class="text-sm border border-gray-400 text-gray-900 rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500" disabled><?php echo $description; ?></textarea>
                </div>
                <div class="flex flex-col h-full">
                    <label for="after" class="pb-2 pt-2 font-medium text-xs text-[#5d5d5d]">Po</label>
                    <div name="after" id="after" cols="30" rows="5" class="break-words h-full text-sm border border-gray-400 text-gray-900 rounded-xl px-4 py-2 focus:outline-none focus:border-indigo-500 transition-all duration-500" disabled><?php echo $after; ?></div>
                </div>
            </div>
        </div>
    </section>
<script>
    highlightChanges();
</script>