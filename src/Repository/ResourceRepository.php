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
}
