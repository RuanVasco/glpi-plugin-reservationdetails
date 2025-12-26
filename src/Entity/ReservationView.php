<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonGLPI;
use CommonDBTM;
use Session;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Reservationdetails\Repository\ReservationRepository;
use GlpiPlugin\Reservationdetails\Utils;

class ReservationView extends CommonGLPI {

    public static $rightname = 'plugin_reservationdetails_reservations';

    public static function getTypeName($nb = 0) {
        return __('Visualizar Reservas');
    }

    public static function getIcon() {
        return 'fas fa-calendar-check';
    }

    public static function canView(): bool {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * Get tab name for ReservationItem
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item instanceof \ReservationItem || $item->getType() === 'ReservationItem') {
            if (self::canView()) {
                return '<i class="' . self::getIcon() . '"></i>&nbsp;' . self::getTypeName();
            }
        }
        return '';
    }

    /**
     * Display tab content for ReservationItem
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item instanceof \ReservationItem || $item->getType() === 'ReservationItem') {
            self::showReservationsList();
        }
        return true;
    }

    /**
     * Show reservations list
     * @param bool $filterByCurrentUser If true, only show reservations of the logged in user
     */
    public static function showReservationsList(bool $filterByCurrentUser = false): void {
        global $DB;

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 15;
        $status = 'open';
        
        // Get userId to filter if needed
        $userId = null;
        if ($filterByCurrentUser) {
            $userId = Session::getLoginUserID();
        }

        $reservationRepository = new ReservationRepository($DB);
        $reservations = $reservationRepository->getReservationsList($status, $page, $perPage, 'begin', 'ASC', '', $userId);
        $totalCount = $reservationRepository->getReservationsCount($status, '', $userId);
        $totalPages = ceil($totalCount / $perPage);

        $columns = ['Item', 'Usuário', 'Início', 'Fim'];
        $values = [];

        foreach ($reservations as $res) {
            $values[] = [
                'id'    => $res['id'],
                'item'  => $res['item'],
                'user'  => $res['user'],
                'begin' => Utils::formatToBr($res['begin']),
                'end'   => Utils::formatToBr($res['end'])
            ];
        }

        $loader = new TemplateRenderer();
        $loader->display('@reservationdetails/reservations_list.html.twig', [
            'columns'     => $columns,
            'values'      => $values,
            'view'        => 'true',
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'perPage'     => $perPage,
            'totalCount'  => $totalCount,
            'filterByUser' => $filterByCurrentUser
        ]);
    }
}
