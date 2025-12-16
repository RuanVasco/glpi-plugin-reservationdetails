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

    public static function create($idResource, $idReservation) {
        global $DB;

        $resourceRepository  = new ResourceRepository($DB);
        $reservationRepository = new ReservationRepository($DB);

        $reservationData = $reservationRepository->findById($idReservation);
        $resourceData = $resourceRepository->findById($idResource);

        $iditem = $reservationData['reservationitems_id'];
        $iditemRes = $resourceData['reservationitems_id'];

        if ($iditem == $iditemRes) {
            if ($resourceRepository->isAvailable($resourceData['plugin_reservationdetails_resources_id'], $reservationData['begin'], $reservationData['end'])) {
                $resourceRepository->linkResourceToReservation($idResource, $idReservation);

                $resourceTarget = $resourceRepository->findById($resourceData['plugin_reservationdetails_resources_id']);

                if ($resourceTarget['ticket_entities_id']) {
                    $itemName = $reservationRepository->getReservationItemName($iditemRes);
                    $ticket = [
                        'entities_id'       =>  $resourceTarget['ticket_entities_id'],
                        'name'              =>  'Reserva para ' . $resourceTarget['name'],
                        'content'           =>  'Reserva para o ' . $resourceTarget['name'] . ' na data ' . $reservationData['begin'] . '\n' . $itemName,
                        'date'              =>  date('Y-m-d h:i:s', time()),
                        'requesttypes_id'   =>  1,
                        'status'            =>  1
                    ];

                    $track = new \Ticket();
                    //$track->check(-1, CREATE, $ticket);
                    $track->add($ticket);
                }

                return true;
            } else {
                Session::addMessageAfterRedirect(
                    __('Recurso não disponível'),
                    false,
                    ERROR
                );
            }
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
