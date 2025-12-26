<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonGLPI;
use Session;

/**
 * MyReservations - Menu entry for helpdesk interface
 */
class MyReservations extends CommonGLPI {
    
    public static function getTypeName($nb = 0) {
        return __('Minhas Reservas');
    }

    public static function getIcon() {
        return 'fas fa-calendar-check';
    }

    public static function canView(): bool {
        return Session::getLoginUserID() !== false;
    }

    public static function getMenuName() {
        return self::getTypeName();
    }

    public static function getMenuContent() {
        global $CFG_GLPI;
        
        return [
            'title' => self::getTypeName(),
            'page'  => $CFG_GLPI['root_doc'] . '/plugins/reservationdetails/front/my_reservations.php',
            'icon'  => self::getIcon()
        ];
    }

    public static function getSearchURL($full = true) {
        global $CFG_GLPI;
        
        if ($full) {
            return $CFG_GLPI['url_base'] . '/plugins/reservationdetails/front/my_reservations.php';
        }
        return $CFG_GLPI['root_doc'] . '/plugins/reservationdetails/front/my_reservations.php';
    }
}
