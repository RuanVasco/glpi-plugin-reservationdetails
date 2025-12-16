<?php

namespace GlpiPlugin\Reservationdetails\Repository;

use GlpiPlugin\Reservationdetails\Entity\Resource;
use DB;

class ResourceRepository {

    public function __construct(
        private DB $db
    ) {
    }

    public function findById($id): ?Resource {
        $resource = new Resource();

        if ($resource->getFromDB($id)) {
            return $resource;
        }

        return null;
    }

    public function findAll(): \Generator {
        $table = Resource::getTable();

        $iterator = $this->db->request(['FROM' => $table]);

        foreach ($iterator as $row) {
            $resource = new Resource();

            $resource->fields = $row;
            yield $resource;
        }
    }

    public function isAvailable(int $resourceId, string $dateStart, string $dateEnd): bool {
        $resource = $this->findById($resourceId);

        if (!$resource) {
            return false;
        }

        $stockTotal = (int) $resource->fields['stock'];

        if ($stockTotal <= 0) {
            return false;
        }

        $result = $this->db->request([
            'SELECT' => ['COUNT' => 'total'],
            'FROM'   => 'glpi_reservations',
            'INNER JOIN' => [
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservationitems',
                        'id',
                        'glpi_reservations',
                        'reservationitems_id'
                    ]
                ],
                'glpi_plugin_reservationdetails_resources_reservationsitems' => [
                    'FKEY' => [
                        'glpi_plugin_reservationdetails_resources_reservationsitems',
                        'reservationitems_id',
                        'glpi_reservationitems',
                        'id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_plugin_reservationdetails_resources_reservationsitems.plugin_reservationdetails_resources_id' => $resourceId,
                'glpi_reservations.begin' => ['<', $dateEnd],
                'glpi_reservations.end'   => ['>', $dateStart]
            ]
        ])->current();

        $usageCount = (int)$result['total'];

        return $usageCount < $stockTotal;
    }

    public function linkResourceToReservation(int $resourceItemId, int $reservationId): bool {
        $result = $this->db->insert(
            'glpi_plugin_reservationdetails_reservations_resources',
            [
                'plugin_reservationdetails_resources_reservationsitems_id' => $resourceItemId,
                'plugin_reservationdetails_reservations_id'                => $reservationId
            ]
        );

        return (bool) $result;
    }

    public function linkReservationItems(int $resourceId, array $input): void {
        foreach ($input as $key => $value) {
            if (strpos($key, 'item_id_') === 0) {
                $this->db->insert('glpi_plugin_reservationdetails_resources_reservationsitems', [
                    'plugin_reservationdetails_resources_id' => $resourceId,
                    'reservationitems_id'                    => (int) $value
                ]);
            }
        }
    }

    public function syncReservationItems(int $resourceId, array $input): void {
        $this->db->delete('glpi_plugin_reservationdetails_resources_reservationsitems', [
            'plugin_reservationdetails_resources_id' => $resourceId
        ]);

        $this->linkReservationItems($resourceId, $input);
    }

    public function getOccupiedResourceIds(string $start, string $end): array {

        $iterator = $this->db->request([
            'SELECT'   => 'context.plugin_reservationdetails_resources_id',
            'DISTINCT' => true,
            'FROM'     => 'glpi_plugin_reservationdetails_reservations_resources AS pivot',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_resources_reservationsitems AS context' => [
                    'ON' => [
                        'pivot'   => 'plugin_reservationdetails_resources_reservationsitems_id',
                        'context' => 'id'
                    ]
                ],
                'glpi_plugin_reservationdetails_reservations AS pres' => [
                    'ON' => [
                        'pivot' => 'plugin_reservationdetails_reservations_id',
                        'pres'  => 'id'
                    ]
                ],
                'glpi_reservations AS gres' => [
                    'ON' => [
                        'pres' => 'reservations_id',
                        'gres' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'gres.begin'      => ['<', $end],
                'gres.end'        => ['>', $start]
            ]
        ]);

        $occupiedIds = [];
        foreach ($iterator as $row) {
            $occupiedIds[] = $row['plugin_reservationdetails_resources_id'];
        }

        return $occupiedIds;
    }

    public function findAvailableResources(int $parentTypeId, array $excludedIds = []): array {

        $criteria = [
            'SELECT' => 'res.*',
            'FROM'   => 'glpi_plugin_reservationdetails_resources_reservationsitems AS link',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_resources AS res' => [
                    'ON' => [
                        'link' => 'plugin_reservationdetails_resources_id',
                        'res'  => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'link.reservationitems_id' => $parentTypeId,
            ]
        ];

        if (!empty($excludedIds)) {
            $criteria['WHERE']['NOT'] = [
                'res.id' => $excludedIds
            ];
        }

        $iterator = $this->db->request($criteria);

        $results = [];
        foreach ($iterator as $row) {
            $results[] = $row;
        }

        return $results;
    }
}
