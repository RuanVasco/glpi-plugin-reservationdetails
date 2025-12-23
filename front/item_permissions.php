<?php

use GlpiPlugin\Reservationdetails\Entity\ItemPermission;

include('../../../inc/includes.php');

// Redirect to the form page which handles the custom display
Html::redirect(ItemPermission::getFormURL());
