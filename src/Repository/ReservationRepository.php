<?php

namespace GlpiPlugin\Reservationdetails\Repository;

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use \DB;
use \ReservationItem;

class ReservationRepository {

    public function __construct(
        private DB $db
    ) {
    }

    public function findById(int $id): ?Reservation {
        $resource = new Reservation();

        if ($resource->getFromDB($id)) {
            return $resource;
        }

        return null;
    }

    public function findStandardById(int $id): ?\Reservation {
        $reservation = new \Reservation();

        if ($reservation->getFromDB($id)) {
            return $reservation;
        }

        return null;
    }

    public function getReservationItemName(int $reservationItemId): string {
        $resItem = new ReservationItem();

        if (!$resItem->getFromDB($reservationItemId)) {
            return "";
        }

        return $this->getItemName(
            $resItem->fields['itemtype'],
            $resItem->fields['items_id']
        );
    }

    public function getItemName(string $itemtype, int $itemsId): string {
        if (!class_exists($itemtype)) {
            return '';
        }

        $table = $itemtype::getTable();

        return \Dropdown::getDropdownName($table, $itemsId) ?? '';
    }

    public function getAllActiveItems(): array {
        $item = new ReservationItem();

        return $item->find(['is_active' => 1]);
    }

    /**
     * Get reservations list by status (open or closed)
     */
    public function getReservationsList(string $status = 'open'): array {
        $now = date('Y-m-d H:i:s');
        
        $criteria = [
            'SELECT' => [
                'glpi_reservations.id',
                'glpi_reservations.begin',
                'glpi_reservations.end',
                'glpi_reservations.users_id',
                'glpi_reservationitems.itemtype',
                'glpi_reservationitems.items_id'
            ],
            'FROM' => 'glpi_reservations',
            'INNER JOIN' => [
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservations' => 'reservationitems_id',
                        'glpi_reservationitems' => 'id'
                    ]
                ]
            ],
            'ORDER' => ['glpi_reservations.begin DESC']
        ];

        if ($status === 'open') {
            $criteria['WHERE'] = ['glpi_reservations.end' => ['>', $now]];
        } else {
            $criteria['WHERE'] = ['glpi_reservations.end' => ['<=', $now]];
        }

        $iterator = $this->db->request($criteria);
        $results = [];

        foreach ($iterator as $row) {
            $user = new \User();
            $userName = '';
            if ($user->getFromDB($row['users_id'])) {
                $userName = $user->getFriendlyName();
            }

            $itemName = $this->getItemName($row['itemtype'], $row['items_id']);

            $results[] = [
                'id'    => $row['id'],
                'item'  => $itemName,
                'user'  => $userName,
                'begin' => $row['begin'],
                'end'   => $row['end']
            ];
        }

        return $results;
    }

    /**
     * Get reservation details with resources
     */
    public function getReservationDetails(int $reservationId): ?array {
        $reservation = $this->findStandardById($reservationId);
        
        if (!$reservation) {
            return null;
        }

        $user = new \User();
        $userName = '';
        if ($user->getFromDB($reservation->fields['users_id'])) {
            $userName = $user->getFriendlyName();
        }

        $resItem = new ReservationItem();
        $itemName = '';
        if ($resItem->getFromDB($reservation->fields['reservationitems_id'])) {
            $itemName = $this->getItemName(
                $resItem->fields['itemtype'],
                $resItem->fields['items_id']
            );
        }

        // Get associated resources
        $recursos = [];
        $resourceQuery = $this->db->request([
            'SELECT' => ['res.name'],
            'FROM' => 'glpi_plugin_reservationdetails_reservations AS pres',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_reservations_resources AS pivot' => [
                    'ON' => [
                        'pivot' => 'plugin_reservationdetails_reservations_id',
                        'pres'  => 'id'
                    ]
                ],
                'glpi_plugin_reservationdetails_resources_reservationsitems AS link' => [
                    'ON' => [
                        'pivot' => 'plugin_reservationdetails_resources_reservationsitems_id',
                        'link'  => 'id'
                    ]
                ],
                'glpi_plugin_reservationdetails_resources AS res' => [
                    'ON' => [
                        'link' => 'plugin_reservationdetails_resources_id',
                        'res'  => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'pres.reservations_id' => $reservationId
            ]
        ]);

        foreach ($resourceQuery as $resource) {
            $recursos[] = ['name' => $resource['name']];
        }

        return [
            'user'     => $userName,
            'itemName' => $itemName,
            'begin'    => \GlpiPlugin\Reservationdetails\Utils::formatToBr($reservation->fields['begin']),
            'end'      => \GlpiPlugin\Reservationdetails\Utils::formatToBr($reservation->fields['end']),
            'comment'  => $reservation->fields['comment'] ?? '',
            'recursos' => $recursos
        ];
    }
}

