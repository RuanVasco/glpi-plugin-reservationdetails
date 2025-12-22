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
     * Get reservations list by status (open or closed) with pagination, sorting and search
     */
    public function getReservationsList(string $status = 'open', int $page = 1, int $perPage = 15, string $sortField = 'begin', string $sortDir = 'ASC', string $search = ''): array {
        $now = date('Y-m-d H:i:s');
        $offset = ($page - 1) * $perPage;
        
        // Validate sort field
        $allowedFields = ['begin', 'end', 'item', 'user'];
        if (!in_array($sortField, $allowedFields)) {
            $sortField = 'begin';
        }
        
        // Validate sort direction
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        
        // Map sort field to actual column
        $sortColumn = match($sortField) {
            'begin' => 'glpi_reservations.begin',
            'end'   => 'glpi_reservations.end',
            'item'  => 'glpi_reservationitems.items_id',
            'user'  => 'glpi_reservations.users_id',
            default => 'glpi_reservations.begin'
        };
        
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
            'ORDER' => ["$sortColumn $sortDir"],
            'START' => $offset,
            'LIMIT' => $perPage
        ];

        if ($status === 'open') {
            $criteria['WHERE'] = ['glpi_reservations.end' => ['>', $now]];
        } else {
            $criteria['WHERE'] = ['glpi_reservations.end' => ['<=', $now]];
        }

        $iterator = $this->db->request($criteria);
        $results = [];
        $searchLower = strtolower($search);

        foreach ($iterator as $row) {
            $user = new \User();
            $userName = '';
            if ($user->getFromDB($row['users_id'])) {
                $userName = $user->getFriendlyName();
            }

            $itemName = $this->getItemName($row['itemtype'], $row['items_id']);

            // Apply search filter if provided
            if (!empty($search)) {
                $matchItem = strpos(strtolower($itemName), $searchLower) !== false;
                $matchUser = strpos(strtolower($userName), $searchLower) !== false;
                $matchDate = strpos($row['begin'], $search) !== false || strpos($row['end'], $search) !== false;
                
                if (!$matchItem && !$matchUser && !$matchDate) {
                    continue;
                }
            }

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
     * Get total count of reservations by status and optional search
     */
    public function getReservationsCount(string $status = 'open', string $search = ''): int {
        // If search is provided, we need to count differently since we filter in PHP
        if (!empty($search)) {
            // Get all matching records (without limit) and count
            $allResults = $this->getReservationsList($status, 1, 9999, 'begin', 'ASC', $search);
            return count($allResults);
        }
        
        $now = date('Y-m-d H:i:s');
        
        $criteria = [
            'COUNT' => 'total',
            'FROM' => 'glpi_reservations'
        ];

        if ($status === 'open') {
            $criteria['WHERE'] = ['end' => ['>', $now]];
        } else {
            $criteria['WHERE'] = ['end' => ['<=', $now]];
        }

        $result = $this->db->request($criteria)->current();
        return (int)$result['total'];
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
            'FROM' => 'glpi_plugin_reservationdetails_resources_reservationsitems AS link',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_resources AS res' => [
                    'ON' => [
                        'link' => 'plugin_reservationdetails_resources_id',
                        'res'  => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'link.reservations_id' => $reservationId
            ]
        ]);

        foreach ($resourceQuery as $resource) {
            $recursos[] = ['name' => $resource['name']];
        }

        // Get custom field values for this reservation
        $customFieldValues = [];
        $cfQuery = $this->db->request([
            'SELECT' => ['cf.field_label', 'cfv.value'],
            'FROM'   => 'glpi_plugin_reservationdetails_customfields_values AS cfv',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_customfields AS cf' => [
                    'ON' => [
                        'cf'  => 'id',
                        'cfv' => 'customfields_id'
                    ]
                ]
            ],
            'WHERE' => ['cfv.reservations_id' => $reservationId],
            'ORDER' => 'cf.field_order ASC'
        ]);

        foreach ($cfQuery as $cf) {
            $customFieldValues[] = [
                'label' => $cf['field_label'],
                'value' => $cf['value']
            ];
        }

        return [
            'user'         => $userName,
            'itemName'     => $itemName,
            'begin'        => \GlpiPlugin\Reservationdetails\Utils::formatToBr($reservation->fields['begin']),
            'end'          => \GlpiPlugin\Reservationdetails\Utils::formatToBr($reservation->fields['end']),
            'comment'      => $reservation->fields['comment'] ?? '',
            'recursos'     => $recursos,
            'customFields' => $customFieldValues
        ];
    }
}

