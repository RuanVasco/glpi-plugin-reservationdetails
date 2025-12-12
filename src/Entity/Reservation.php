<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;

class Reservation extends CommonDBTM {
    public static $rightname = 'plugin_reservationdetails_reservations';

    public static function addFieldsInReservationForm() {
        $loader = new TemplateRenderer();
        $loader->display('@reservationdetails/reserve_item_form.html.twig');
    }
}
