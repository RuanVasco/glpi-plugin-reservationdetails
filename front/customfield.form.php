<?php

use GlpiPlugin\Reservationdetails\Entity\CustomField;

include("../../../inc/includes.php");

Session::checkLoginUser();

$customField = new CustomField();

if (isset($_POST['add'])) {
    $customField->check(-1, CREATE, $_POST);
    // Remove id for new records to allow auto-increment
    if (isset($_POST['id']) && $_POST['id'] <= 0) {
        unset($_POST['id']);
    }
    // Sync name with field_label for CommonDropdown compatibility
    if (isset($_POST['field_label'])) {
        $_POST['name'] = $_POST['field_label'];
    }
    if ($customField->add($_POST)) {
        Session::addMessageAfterRedirect(__('Item successfully added'), true, INFO);
    }
    Html::back();

} else if (isset($_POST['update'])) {
    $customField->check($_POST['id'], UPDATE);
    // Sync name with field_label for CommonDropdown compatibility
    if (isset($_POST['field_label'])) {
        $_POST['name'] = $_POST['field_label'];
    }
    if ($customField->update($_POST)) {
        Session::addMessageAfterRedirect(__('Item successfully updated'), true, INFO);
    }
    Html::back();

} else if (isset($_POST['purge'])) {
    $customField->check($_POST['id'], PURGE);
    if ($customField->delete($_POST, 1)) {
        Session::addMessageAfterRedirect(__('Item successfully deleted'), true, INFO);
    }
    $customField->redirectToList();

} else {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : -1;
    
    Html::header(
        CustomField::getTypeName(Session::getPluralNumber()),
        $_SERVER['PHP_SELF'],
        'config',
        'commondropdown',
        CustomField::class
    );
    
    $customField->display(['id' => $id]);
    
    Html::footer();
}
