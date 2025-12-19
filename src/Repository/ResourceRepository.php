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

    public function linkResourceToReservation(int $resourceId, int $reservationItemId, int $glpiReservationId): bool {
        // Busca o ID correto na tabela de vínculo resource-reservationitem
        $linkRecord = $this->db->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_plugin_reservationdetails_resources_reservationsitems',
            'WHERE'  => [
                'plugin_reservationdetails_resources_id' => $resourceId,
                'reservationitems_id'                    => $reservationItemId
            ]
        ])->current();

        if (!$linkRecord) {
            return false;
        }

        // Busca o ID do plugin reservation a partir do ID da reserva GLPI nativa
        $pluginReservation = $this->db->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_plugin_reservationdetails_reservations',
            'WHERE'  => [
                'reservations_id' => $glpiReservationId
            ]
        ])->current();

        if (!$pluginReservation) {
            return false;
        }

        $result = $this->db->insert(
            'glpi_plugin_reservationdetails_reservations_resources',
            [
                'plugin_reservationdetails_resources_reservationsitems_id' => $linkRecord['id'],
                'plugin_reservationdetails_reservations_id'                => $pluginReservation['id']
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

    public function findAvailableResources(int $parentTypeId, string $start, string $end): array {
        $criteriaResources = [
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
                'link.reservationitems_id' => $parentTypeId
            ]
        ];

        $iteratorResources = $this->db->request($criteriaResources);

        $resources = [];
        foreach ($iteratorResources as $row) {
            $resources[$row['id']] = $row;
            $resources[$row['id']]['current_usage'] = 0;
        }

        if (empty($resources)) {
            return [];
        }

        $criteriaUsage = [
            'SELECT' => 'link.plugin_reservationdetails_resources_id AS res_id',
            'FROM'   => 'glpi_plugin_reservationdetails_reservations_resources AS usage_pivot',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_resources_reservationsitems AS link' => [
                    'ON' => [
                        'usage_pivot' => 'plugin_reservationdetails_resources_reservationsitems_id',
                        'link'        => 'id'
                    ]
                ],
                'glpi_plugin_reservationdetails_reservations AS plugin_res' => [
                    'ON' => [
                        'usage_pivot' => 'plugin_reservationdetails_reservations_id',
                        'plugin_res'  => 'id'
                    ]
                ],
                'glpi_reservations AS glpi_res' => [
                    'ON' => [
                        'plugin_res' => 'reservations_id',
                        'glpi_res'   => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'link.plugin_reservationdetails_resources_id' => array_keys($resources),
                'glpi_res.begin' => ['<', $end],
                'glpi_res.end'   => ['>', $start]
            ]
        ];

        $iteratorUsage = $this->db->request($criteriaUsage);

        foreach ($iteratorUsage as $usage) {
            $resId = $usage['res_id'];
            if (isset($resources[$resId])) {
                $resources[$resId]['current_usage']++;
            }
        }

        $finalList = [];

        foreach ($resources as $resource) {
            $stock = $resource['stock'];
            $count = $resource['current_usage'];

            $isAvailable = true;

            if (!is_null($stock) && $count >= $stock) {
                $isAvailable = false;
            }

            $resource['availability'] = $isAvailable;

            $finalList[] = $resource;
        }

        return $finalList;
    }

    public function getAvailabilityResource(int $resourceId, string $start, string $end): bool {

        $resource = $this->db->request([
            'SELECT' => 'stock',
            'FROM'   => 'glpi_plugin_reservationdetails_resources',
            'WHERE'  => ['id' => $resourceId]
        ])->current();

        if (!$resource || is_null($resource['stock'])) {
            return true;
        }

        $stockLimit = (int)$resource['stock'];

        $result = $this->db->request([
            'COUNT'  => 'total_usage',
            'FROM'   => 'glpi_plugin_reservationdetails_reservations_resources AS usage_pivot',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_resources_reservationsitems AS context' => [
                    'ON' => [
                        'usage_pivot' => 'plugin_reservationdetails_resources_reservationsitems_id',
                        'context'     => 'id'
                    ]
                ],
                'glpi_plugin_reservationdetails_reservations AS plugin_res' => [
                    'ON' => [
                        'usage_pivot' => 'plugin_reservationdetails_reservations_id',
                        'plugin_res'  => 'id'
                    ]
                ],
                'glpi_reservations AS glpi_res' => [
                    'ON' => [
                        'plugin_res' => 'reservations_id',
                        'glpi_res'   => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'context.plugin_reservationdetails_resources_id' => $resourceId,
                'glpi_res.begin'      => ['<', $end],
                'glpi_res.end'        => ['>', $start],
            ]
        ])->current();

        $currentUsage = (int)$result['total_usage'];

        if ($currentUsage >= $stockLimit) {
            return false;
        }

        return true;
    }
}
