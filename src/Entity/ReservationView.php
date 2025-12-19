<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonGLPI;
use Session;

class ReservationView extends CommonGLPI {

    public static $rightname = 'plugin_reservationdetails_reservations';

    public static function getTypeName($nb = 0) {
        return __('Reservations View');
    }

    public static function getIcon() {
        return 'fas fa-calendar-check';
    }

    public static function getMenuName() {
        return __('Reservations View');
    }

    public static function canView(): bool {
        return Session::haveRight(self::$rightname, READ);
    }

    public static function getMenuContent() {
        $menu = [];

        if (self::canView()) {
            $menu['title'] = self::getMenuName();
            $menu['page']  = '/plugins/reservationdetails/front/reservations.php';
            $menu['icon']  = self::getIcon();
        }

        return $menu;
    }

    static function getSearchURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/reservations.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/reservations.php";
    }
}
