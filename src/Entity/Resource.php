<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use GlpiPlugin\Reservationdetails\Repository\ReservationRepository;
use GlpiPlugin\Reservationdetails\Repository\ResourceRepository;
use CommonDropdown;
use Session;
use Html;
use Glpi\Application\View\TemplateRenderer;

class Resource extends CommonDropdown {
    public static $rightname = 'plugin_reservationdetails_resources';

    public static function getTypeName($nb = 0) {
        return _n('Resource', 'Resources', $nb);
    }

    public static function getIcon() {
        return 'fas fa-mug-saucer';
    }

    public static function getMenuName() {
        return _n('Resource', 'Resources', 2);
    }

    public static function canView(): bool {
        return true;
    }

    public static function canCreate(): bool {
        return true;
    }

    public static function canUpdate(): bool {
        return true;
    }

    public static function canDelete(): bool {
        return true;
    }

    public static function canPurge(): bool {
        return true;
    }

    static function getFormURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/resource.form.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/resource.form.php";
    }

    static function getSearchURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/resource.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/resource.php";
    }

    public static function getTable($classname = null) {
        return 'glpi_plugin_reservationdetails_resources';
    }

    public function rawSearchOptions() {
        $table = self::getTable();

        $tab = [];

        $tab[] = [
            'id'                 => '2',
            'table'              => $table,
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => true,
            'itemtype'           => self::class
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $table,
            'field'              => 'stock',
            'name'               => __('Stock'),
            'datatype'           => 'number',
            'massiveaction'      => true
        ];

        return $tab;
    }

    public function getAll() {
        global $DB;
        $response = [];

        $resourceRepository  = new ResourceRepository($DB);
        $results = $resourceRepository->findAll();

        foreach ($results as $result) {
            $response[] = [
                'id'    =>  $result['id'],
                'name'  =>  $result['name']
            ];
        }

        return $response;
    }

    public static function create(int $idResource, int $idReservation): bool {
        global $DB;

        $resourceRepository    = new ResourceRepository($DB);
        $reservationRepository = new ReservationRepository($DB);

        $reservationObj = $reservationRepository->findStandardById($idReservation);

        if (!$reservationObj) {
            return false;
        }

        $reservationBegin  = $reservationObj->fields['begin'];
        $reservationEnd    = $reservationObj->fields['end'];
        $reservationRoomId = $reservationObj->fields['reservationitems_id'];

        $resourceData = $DB->request([
            'FROM'  => 'glpi_plugin_reservationdetails_resources',
            'WHERE' => ['id' => $idResource]
        ])->current();

        if (!$resourceData) {
            return false;
        }

        $linkCheck = $DB->request([
            'COUNT' => 'c',
            'FROM'  => 'glpi_plugin_reservationdetails_resources_reservationsitems',
            'WHERE' => [
                'plugin_reservationdetails_resources_id' => $idResource,
                'reservationitems_id'                    => $reservationRoomId
            ]
        ])->current();

        if ($linkCheck['c'] == 0) {
            \Session::addMessageAfterRedirect(
                __('Este recurso não está disponível para este item de reserva.'),
                false,
                ERROR
            );
            return false;
        }

        $isAvailable = $resourceRepository->getAvailabilityResource($idResource, $reservationBegin, $reservationEnd);

        if ($isAvailable) {

            $resourceRepository->linkResourceToReservation($idResource, $reservationRoomId, $idReservation);
            if (!empty($resourceData['ticket_entities_id'])) {

                $roomName = $reservationRepository->getReservationItemName($reservationRoomId);

                $ticket = [
                    'entities_id'     => $resourceData['ticket_entities_id'],
                    'name'            => 'Reserva de Recurso: ' . $resourceData['name'],
                    'content'         => sprintf(
                        "Reserva solicitada para o recurso: %s\nData: %s\nLocal: %s",
                        $resourceData['name'],
                        $reservationBegin,
                        $roomName
                    ),
                    'date'            => date('Y-m-d H:i:s'),
                    'requesttypes_id' => 1,
                    'status'          => 1
                ];

                $track = new \Ticket();
                $track->add($ticket);
            }

            return true;
        } else {
            \Session::addMessageAfterRedirect(
                __('Recurso não disponível para o horário selecionado (Estoque esgotado).'),
                false,
                ERROR
            );
        }

        return false;
    }

    public function showForm($ID, array $options = []) {
        global $DB;
        $resource = [];
        $items = [];
        $actualItems = [];

        $resourceRepository = new ResourceRepository($DB);
        $reservationRepository = new ReservationRepository($DB);

        $entity = new \Entity();
        $entities = $entity->find([], ['completename']);

        $resourcesReservationItems = $DB->request([
            'FROM'       => 'glpi_reservationitems',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_resources_reservationsitems' => [
                    'ON' => [
                        'glpi_plugin_reservationdetails_resources_reservationsitems' => 'reservationitems_id',
                        'glpi_reservationitems'                                      => 'id'
                    ]
                ]
            ],
            'WHERE'      => [
                'glpi_plugin_reservationdetails_resources_reservationsitems.plugin_reservationdetails_resources_id' => $ID
            ]
        ]);

        $items = [];
        $reservationItems = $reservationRepository->getAllActiveItems();
        foreach ($reservationItems as $reservationItem) {

            $type = $reservationItem['itemtype'];
            $id   = $reservationItem['items_id'];

            if ($type && class_exists($type)) {
                /** @var \CommonDBTM $realItem */
                $realItem = new $type();

                if ($realItem->getFromDB($id)) {

                    $items[] = [
                        'id'           => $reservationItem['id'],
                        'itemtype'     => $type,
                        'itemTypeName' => $realItem->getName(),
                        'itemTypeId'   => $id
                    ];
                }
            }
        }

        foreach ($resourcesReservationItems as $i) {
            $actualItems[] = [
                'id'    => $i['reservationitems_id'],
                'name'  => $reservationRepository->getItemName($i['itemtype'], $i['items_id'])
            ];
        }

        if ($ID > 0) {
            $resource = $resourceRepository->findByID($ID);
            $resource = [
                'name'                         =>  $resource->fields['name'],
                'stock'                        =>  $resource->fields['stock'],
                'type'                         =>  $resource->fields['type'],
                'ticket_entities_id'           =>  $resource->fields['ticket_entities_id']
            ];
        }

        $loader = new TemplateRenderer();
        $loader->display(
            '@reservationdetails/resource_form.html.twig',
            [
                'id'            =>  $ID,
                'current_value' =>  $resource,
                'items_value'   =>  $items,
                'current_items' =>  $actualItems,
                'entities'      =>  $entities,
                'itemtype'      => $this
            ]
        );

        return true;
    }
}
