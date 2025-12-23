<?php

use GlpiPlugin\Reservationdetails\Entity\ItemPermission;

include("../../../inc/includes.php");

Session::checkRight('config', READ);

// Correct breadcrumb: Configurar > Listas Suspensas
Html::header(__('Permissões de Reserva'), $_SERVER['PHP_SELF'], 'config', 'commondropdown');

global $DB;

// Handle form submission
if (isset($_POST['save'])) {
    Session::checkRight('config', UPDATE);
    
    $itemtype = $_POST['itemtype'] ?? '';
    $profileIds = $_POST['profiles_ids'] ?? [];
    
    if (!empty($itemtype)) {
        ItemPermission::saveProfilesForItemtype($itemtype, $profileIds);
        Session::addMessageAfterRedirect(__('Permissões salvas com sucesso'), true, INFO);
    }
    
    Html::redirect(PLUGIN_RESERVATIONDETAILS_WEBDIR . '/front/itemtype_permissions.php');
}

// Get all reservable itemtypes (from ReservationItem)
$reservableTypes = [];
$iterator = $DB->request([
    'SELECT'   => 'itemtype',
    'DISTINCT' => true,
    'FROM'     => 'glpi_reservationitems',
    'WHERE'    => ['is_active' => 1]
]);
foreach ($iterator as $row) {
    $class = $row['itemtype'];
    if (class_exists($class)) {
        $reservableTypes[$class] = $class::getTypeName(2);
    }
}
asort($reservableTypes);

// Get all profiles
$profiles = [];
$profileIterator = $DB->request([
    'SELECT' => ['id', 'name'],
    'FROM'   => 'glpi_profiles',
    'ORDER'  => ['name ASC']
]);
foreach ($profileIterator as $row) {
    $profiles[$row['id']] = $row['name'];
}

// Get current permissions
$currentPermissions = ItemPermission::getAllItemtypePermissions();

echo "<div class='center'>";
echo "<h2>" . __('Permissões de Reserva por Tipo de Ativo') . "</h2>";
echo "<p class='text-muted'>" . __('Configure quais perfis podem reservar cada tipo de ativo. Deixe vazio para permitir todos.') . "</p>";

echo "<table class='tab_cadre_fixe'>";
echo "<thead>";
echo "<tr class='tab_bg_1'>";
echo "<th>" . __('Tipo de Ativo') . "</th>";
echo "<th>" . __('Perfis Permitidos') . "</th>";
echo "<th>" . __('Ações') . "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach ($reservableTypes as $itemtype => $typeName) {
    $allowedProfiles = $currentPermissions[$itemtype] ?? [];
    
    echo "<tr class='tab_bg_1'>";
    echo "<td><strong>$typeName</strong><br><small class='text-muted'>$itemtype</small></td>";
    echo "<td>";
    
    if (empty($allowedProfiles)) {
        echo "<span class='badge bg-success'>" . __('Todos os perfis') . "</span>";
    } else {
        $names = [];
        foreach ($allowedProfiles as $pId) {
            if (isset($profiles[$pId])) {
                $names[] = "<span class='badge bg-primary'>" . $profiles[$pId] . "</span>";
            }
        }
        echo implode(' ', $names);
    }
    
    echo "</td>";
    echo "<td>";
    echo "<button type='button' class='btn btn-sm btn-outline-primary' onclick=\"editPermissions('$itemtype', '$typeName')\">";
    echo "<i class='fas fa-edit'></i> " . __('Editar');
    echo "</button>";
    echo "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";

// Modal for editing
echo "<div class='modal fade' id='editModal' tabindex='-1'>";
echo "<div class='modal-dialog'>";
echo "<div class='modal-content'>";
echo "<form method='post' action='" . PLUGIN_RESERVATIONDETAILS_WEBDIR . "/front/itemtype_permissions.php'>";
echo "<div class='modal-header'>";
echo "<h5 class='modal-title'>" . __('Editar Permissões') . "</h5>";
echo "<button type='button' class='btn-close' data-bs-dismiss='modal'></button>";
echo "</div>";
echo "<div class='modal-body'>";
echo "<input type='hidden' name='itemtype' id='modal_itemtype'>";
echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
echo "<p id='modal_typename'></p>";
echo "<label>" . __('Perfis permitidos') . "</label>";
echo "<select name='profiles_ids[]' id='modal_profiles' multiple class='form-select' style='height: 200px;'>";
foreach ($profiles as $id => $name) {
    echo "<option value='$id'>$name</option>";
}
echo "</select>";
echo "<small class='text-muted'>" . __('Deixe vazio para permitir todos') . "</small>";
echo "</div>";
echo "<div class='modal-footer'>";
echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>" . __('Cancelar') . "</button>";
echo "<button type='submit' name='save' class='btn btn-primary'>" . __('Salvar') . "</button>";
echo "</div>";
echo "</form>";
echo "</div>";
echo "</div>";
echo "</div>";

// JavaScript
echo "<script>
var permissions = " . json_encode($currentPermissions) . ";

function editPermissions(itemtype, typename) {
    document.getElementById('modal_itemtype').value = itemtype;
    document.getElementById('modal_typename').innerHTML = '<strong>' + typename + '</strong>';
    
    var select = document.getElementById('modal_profiles');
    for (var i = 0; i < select.options.length; i++) {
        select.options[i].selected = false;
    }
    
    if (permissions[itemtype]) {
        for (var i = 0; i < select.options.length; i++) {
            if (permissions[itemtype].includes(parseInt(select.options[i].value))) {
                select.options[i].selected = true;
            }
        }
    }
    
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}
</script>";

Html::footer();
