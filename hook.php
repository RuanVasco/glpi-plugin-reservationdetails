<?php

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use GlpiPlugin\Reservationdetails\Entity\Resource;
use GlpiPlugin\Reservationdetails\Entity\Profile;

function plugin_reservationdetails_install() {
    global $DB;

    $migration = new Migration(100);

    if (!$DB->tableExists('glpi_plugin_reservationdetails_resources')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_resources` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(55) NOT NULL,
                    `stock` INT(10),
                    `type`  VARCHAR(10) DEFAULT NULL,
                    `ticket_entities_id` INT(11) UNSIGNED,
                    PRIMARY KEY (`id`),
                    KEY `ticket_entities_id` (`ticket_entities_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query, $DB->error());
    }

    if (!$DB->tableExists('glpi_plugin_reservationdetails_reservations')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_reservations` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `people_quantity` INT(11),
                    `reservations_id` INT(10) UNSIGNED NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `reservations_id` (`reservations_id`),
                    UNIQUE (`reservations_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query, $DB->error());
    }

    if (!$DB->tableExists('glpi_plugin_reservationdetails_resources_reservationsitems')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_resources_reservationsitems` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `plugin_reservationdetails_resources_id` INT(11) UNSIGNED NOT NULL,
                    `reservationitems_id` INT(11) UNSIGNED NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `plugin_reservationdetails_resources_id` (`plugin_reservationdetails_resources_id`),
                    KEY `reservationitems_id` (`reservationitems_id`),
                    CONSTRAINT `fk_plugin_reservationdetails_resources`
                        FOREIGN KEY (`plugin_reservationdetails_resources_id`)
                        REFERENCES `glpi_plugin_reservationdetails_resources` (`id`)
                        ON DELETE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query, $DB->error());
    }

    if (!$DB->tableExists('glpi_plugin_reservationdetails_reservations_resources')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_reservations_resources` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `plugin_reservationdetails_resources_reservationsitems_id` INT(11) UNSIGNED NOT NULL,
                    `plugin_reservationdetails_reservations_id` INT(11) UNSIGNED NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `plugin_reservationdetails_reservations_id` (`plugin_reservationdetails_reservations_id`),
                    KEY `plugin_reservationdetails_resources_reservationsitems_id` (`plugin_reservationdetails_resources_reservationsitems_id`),
                    CONSTRAINT `fk_plugin_reservationdetails_resources_reservationsitems`
                        FOREIGN KEY (`plugin_reservationdetails_resources_reservationsitems_id`)
                        REFERENCES `glpi_plugin_reservationdetails_resources_reservationsitems` (`id`)
                        ON DELETE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query, $DB->error());
    }

    // Custom Fields - Field definitions per itemtype
    if (!$DB->tableExists('glpi_plugin_reservationdetails_customfields')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_customfields` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `itemtype` VARCHAR(100) NOT NULL,
                    `field_name` VARCHAR(100) NOT NULL,
                    `field_label` VARCHAR(255) NOT NULL,
                    `field_type` ENUM('text', 'number', 'textarea', 'dropdown') DEFAULT 'text',
                    `is_mandatory` TINYINT(1) DEFAULT 0,
                    `dropdown_values` TEXT,
                    `field_order` INT(11) DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY `itemtype` (`itemtype`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query, $DB->error());
    }

    // Custom Fields - Values per reservation
    if (!$DB->tableExists('glpi_plugin_reservationdetails_customfields_values')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_customfields_values` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `customfields_id` INT(11) UNSIGNED NOT NULL,
                    `reservations_id` INT(11) UNSIGNED NOT NULL,
                    `value` TEXT,
                    PRIMARY KEY (`id`),
                    KEY `customfields_id` (`customfields_id`),
                    KEY `reservations_id` (`reservations_id`),
                    UNIQUE KEY `field_reservation` (`customfields_id`, `reservations_id`),
                    CONSTRAINT `fk_customfields`
                        FOREIGN KEY (`customfields_id`)
                        REFERENCES `glpi_plugin_reservationdetails_customfields` (`id`)
                        ON DELETE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query, $DB->error());
    }

    // Install profile rights
    Profile::installRights();

    // Add ticket_id column to reservations_resources if not exists (migration)
    if ($DB->tableExists('glpi_plugin_reservationdetails_reservations_resources')) {
        if (!$DB->fieldExists('glpi_plugin_reservationdetails_reservations_resources', 'tickets_id')) {
            $DB->doQueryOrDie(
                "ALTER TABLE `glpi_plugin_reservationdetails_reservations_resources` 
                 ADD COLUMN `tickets_id` INT(11) UNSIGNED DEFAULT NULL,
                 ADD KEY `tickets_id` (`tickets_id`)",
                $DB->error()
            );
        }
    }

    $migration->executeMigration();
    return true;
}

function plugin_reservationdetails_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_reservationdetails_customfields_values',
        'glpi_plugin_reservationdetails_customfields',
        'glpi_plugin_reservationdetails_reservations_resources',
        'glpi_plugin_reservationdetails_resources_reservationsitems',
        'glpi_plugin_reservationdetails_reservations',
        'glpi_plugin_reservationdetails_resources'
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->dropTable($table);
        }
    }

    // Remove profile rights
    Profile::uninstallRights();

    return true;
}

function plugin_reservationdetails_additem_called(CommonDBTM $item) {
    if ($item::getType() == \Reservation::class) {
        $obj = new Reservation;
        $found = false;

        foreach (array_keys($_POST) as $key) {
            if (strpos($key, 'resource_id_') === 0) {
                $found = true;
                break;
            }
        }

        // Check if this is a bulk/recurring reservation
        // Bulk reservations have periodicity[type] set to something other than empty
        $isBulk = false;
        if (isset($_POST['periodicity']) && is_array($_POST['periodicity'])) {
            $isBulk = !empty($_POST['periodicity']['type']);
        }

        if (!$found) {
            // No resources selected - redirect to resource form
            // But skip redirect for bulk reservations to not break the flow
            if (!$isBulk) {
                Html::redirect($obj->getFormURLWithID($item->getID()));
            }
        } else {
            // Resources were selected
            $_POST['reservations_id'] = $item->fields['id'];

            foreach ($_POST as $i => $key) {
                if (strpos($i, 'resource_id_') !== false) {
                    $parts = explode('_', $i);
                    $resourceID = $parts[2];
                    
                    // For bulk reservations, use silent mode (warnings instead of errors)
                    Resource::create($resourceID, $_POST['reservations_id'], $isBulk);
                }
            }

            // Only add plugin reservation record for single reservations
            if (!$isBulk) {
                $obj->check(-1, CREATE, $_POST);
                $obj->add($_POST);
            }
        }
    }
}

function plugin_reservationdetails_params_hook(array $params) {
    if (($params['item'] == new \Reservation())) {
        Reservation::addFieldsInReservationForm();
    }
}

/**
 * Handle reservation deletion - close associated tickets
 */
function plugin_reservationdetails_purgeitem_called(CommonDBTM $item) {
    if ($item::getType() == \Reservation::class) {
        global $DB;
        
        $reservationId = $item->getID();
        
        // Find the plugin reservation record
        $pluginReservation = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_plugin_reservationdetails_reservations',
            'WHERE'  => ['reservations_id' => $reservationId]
        ])->current();
        
        if (!$pluginReservation) {
            return;
        }
        
        // Find associated tickets
        $tickets = $DB->request([
            'SELECT' => 'tickets_id',
            'FROM'   => 'glpi_plugin_reservationdetails_reservations_resources',
            'WHERE'  => [
                'plugin_reservationdetails_reservations_id' => $pluginReservation['id'],
                'tickets_id' => ['>', 0]
            ]
        ]);
        
        foreach ($tickets as $row) {
            if (!empty($row['tickets_id'])) {
                $ticket = new \Ticket();
                if ($ticket->getFromDB($row['tickets_id'])) {
                    // Solve the ticket (status 5 = Solved in GLPI)
                    $ticket->update([
                        'id'     => $row['tickets_id'],
                        'status' => \CommonITILObject::SOLVED,
                        'solution' => 'Reserva cancelada/excluída pelo sistema.'
                    ]);
                    
                    \Session::addMessageAfterRedirect(
                        sprintf(__("Chamado #%d solucionado automaticamente."), $row['tickets_id']),
                        true,
                        INFO
                    );
                }
            }
        }
        
        // Clean up plugin data (cascade will handle resources relationship)
        $DB->delete('glpi_plugin_reservationdetails_reservations', [
            'reservations_id' => $reservationId
        ]);
    }
}
