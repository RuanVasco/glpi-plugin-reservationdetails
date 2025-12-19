<?php

use GlpiPlugin\Reservationdetails\Entity\CustomField;

include("../../../inc/includes.php");

Session::checkLoginUser();

Html::header(
    CustomField::getTypeName(2),
    $_SERVER['PHP_SELF'],
    'config',
    'commondropdown',
    CustomField::class
);

global $DB;

// List custom fields
$iterator = $DB->request([
    'FROM' => CustomField::getTable(),
    'ORDER' => 'name ASC'
]);

echo '<table class="table table-striped">';
echo '<thead><tr>';
echo '<th>' . __('Name') . '</th>';
echo '<th>' . __('Item Type') . '</th>';
echo '<th>' . __('Field Name') . '</th>';
echo '<th>' . __('Type') . '</th>';
echo '<th>' . __('Mandatory') . '</th>';
echo '</tr></thead>';
echo '<tbody>';

foreach ($iterator as $row) {
    $itemtypeName = '';
    if (!empty($row['itemtype']) && class_exists($row['itemtype'])) {
        $itemtypeName = $row['itemtype']::getTypeName(1);
    } else {
        $itemtypeName = $row['itemtype'] ?? '-';
    }
    
    $mandatory = $row['is_mandatory'] ? __('Yes') : __('No');
    $name = $row['name'] ?? $row['field_label'] ?? '';
    
    echo '<tr>';
    echo '<td><a href="' . CustomField::getFormURL() . '?id=' . $row['id'] . '">' . htmlspecialchars($name) . '</a></td>';
    echo '<td>' . htmlspecialchars($itemtypeName) . '</td>';
    echo '<td>' . htmlspecialchars($row['field_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['field_type'] ?? '') . '</td>';
    echo '<td>' . $mandatory . '</td>';
    echo '</tr>';
}

if ($iterator->count() === 0) {
    echo '<tr><td colspan="5" class="text-center">' . __('No item found') . '</td></tr>';
}

echo '</tbody></table>';

Html::footer();
