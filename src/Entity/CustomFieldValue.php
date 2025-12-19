<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonDBTM;
use Session;

class CustomFieldValue extends CommonDBTM {
    public static $rightname = 'plugin_reservationdetails_customfields';

    public static function getTypeName($nb = 0) {
        return _n('Custom Field Value', 'Custom Field Values', $nb);
    }

    public static function getTable($classname = null) {
        return 'glpi_plugin_reservationdetails_customfields_values';
    }

    /**
     * Save custom field values for a reservation
     */
    public static function saveForReservation(int $reservationId, array $fieldValues): void {
        global $DB;

        foreach ($fieldValues as $fieldId => $value) {
            // Check if value already exists
            $existing = $DB->request([
                'FROM'  => self::getTable(),
                'WHERE' => [
                    'customfields_id' => $fieldId,
                    'reservations_id' => $reservationId
                ]
            ])->current();

            if ($existing) {
                // Update
                $DB->update(self::getTable(), [
                    'value' => $value
                ], [
                    'id' => $existing['id']
                ]);
            } else {
                // Insert
                $DB->insert(self::getTable(), [
                    'customfields_id' => $fieldId,
                    'reservations_id' => $reservationId,
                    'value'           => $value
                ]);
            }
        }
    }

    /**
     * Get all custom field values for a reservation
     */
    public static function getForReservation(int $reservationId): array {
        global $DB;

        $values = [];

        $iterator = $DB->request([
            'SELECT' => ['cfv.*', 'cf.field_name', 'cf.field_label', 'cf.field_type'],
            'FROM'   => self::getTable() . ' AS cfv',
            'INNER JOIN' => [
                'glpi_plugin_reservationdetails_customfields AS cf' => [
                    'ON' => [
                        'cf' => 'id',
                        'cfv' => 'customfields_id'
                    ]
                ]
            ],
            'WHERE'  => ['cfv.reservations_id' => $reservationId]
        ]);

        foreach ($iterator as $row) {
            $values[$row['customfields_id']] = [
                'field_name'  => $row['field_name'],
                'field_label' => $row['field_label'],
                'field_type'  => $row['field_type'],
                'value'       => $row['value']
            ];
        }

        return $values;
    }

    /**
     * Delete all values for a reservation
     */
    public static function deleteForReservation(int $reservationId): void {
        global $DB;

        $DB->delete(self::getTable(), [
            'reservations_id' => $reservationId
        ]);
    }
}
