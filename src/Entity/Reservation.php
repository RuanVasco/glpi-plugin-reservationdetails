<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;

class Reservation extends CommonDBTM {
    public static $rightname = 'plugin_reservationdetails_reservations';

    static function getFormURL($full = true) {
        global $CFG_GLPI;

        $url = $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/reservation.form.php";

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/reservation.form.php";
        }

        return $url;
    }

    public static function addFieldsInReservationForm() {
        return true;
        // $loader = new TemplateRenderer();
        // $loader->display('@reservationdetails/reserve_item_form.html.twig');
    }
}
