<?php

use GlpiPlugin\Reservationdetails\Entity\CustomField;

include("../../../inc/includes.php");

Session::checkLoginUser();

$customField = new CustomField();

if (isset($_POST['add'])) {
    $customField->check(-1, CREATE, $_POST);
    if ($customField->add($_POST)) {
        Session::addMessageAfterRedirect(__('Item successfully added'), true, INFO);
    }
    Html::back();

} else if (isset($_POST['update'])) {
    $customField->check($_POST['id'], UPDATE);
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
    $menus = ['config', 'commondropdown', CustomField::class];
    CustomField::displayFullPageForItem($_GET['id'] ?? -1, $menus);
}
