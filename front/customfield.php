<?php

use GlpiPlugin\Reservationdetails\Entity\CustomField;

include("../../../inc/includes.php");

Session::checkLoginUser();

$menus = ['config', 'commondropdown', CustomField::class];
CustomField::displayFullPageForItem(0, $menus, [
    'formoptions' => ''
]);
