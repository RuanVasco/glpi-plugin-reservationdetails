<?php

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use GlpiPlugin\Reservationdetails\Entity\Resource;
use GlpiPlugin\Reservationdetails\Entity\Profile;
use GlpiPlugin\Reservationdetails\Entity\ItemPermission;

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

    if (!$DB->tableExists('glpi_plugin_reservationdetails_resources_reservationsitems')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_resources_reservationsitems` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `plugin_reservationdetails_resources_id` INT(11) UNSIGNED NOT NULL,
                    `reservationitems_id` INT(11) UNSIGNED NOT NULL,
                    `reservations_id` INT(11) UNSIGNED DEFAULT NULL,
                    `tickets_id` INT(11) UNSIGNED DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `plugin_reservationdetails_resources_id` (`plugin_reservationdetails_resources_id`),
                    KEY `reservationitems_id` (`reservationitems_id`),
                    KEY `reservations_id` (`reservations_id`),
                    KEY `tickets_id` (`tickets_id`),
                    CONSTRAINT `fk_plugin_reservationdetails_resources`
                        FOREIGN KEY (`plugin_reservationdetails_resources_id`)
                        REFERENCES `glpi_plugin_reservationdetails_resources` (`id`)
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

    // Asset type permissions - which profiles can reserve which asset types
    if (!$DB->tableExists('glpi_plugin_reservationdetails_itemtypes_profiles')) {
        $query = "CREATE TABLE `glpi_plugin_reservationdetails_itemtypes_profiles` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `itemtype` VARCHAR(100) NOT NULL,
                    `profiles_id` INT(11) UNSIGNED NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `itemtype` (`itemtype`),
                    KEY `profiles_id` (`profiles_id`),
                    UNIQUE KEY `itemtype_profile` (`itemtype`, `profiles_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $DB->doQueryOrDie($query, $DB->error());
    }

    // Install profile rights
    Profile::installRights();

    // Migration: Add new columns to resources_reservationsitems (simplified structure)
    if ($DB->tableExists('glpi_plugin_reservationdetails_resources_reservationsitems')) {
        // Add reservations_id column if not exists
        if (!$DB->fieldExists('glpi_plugin_reservationdetails_resources_reservationsitems', 'reservations_id')) {
            $DB->doQueryOrDie(
                "ALTER TABLE `glpi_plugin_reservationdetails_resources_reservationsitems` 
                 ADD COLUMN `reservations_id` INT(11) UNSIGNED DEFAULT NULL,
                 ADD COLUMN `tickets_id` INT(11) UNSIGNED DEFAULT NULL,
                 ADD KEY `reservations_id` (`reservations_id`),
                 ADD KEY `tickets_id` (`tickets_id`)",
                $DB->error()
            );
        }
    }

    // Add name and comment columns to customfields for CommonDropdown compatibility
    if ($DB->tableExists('glpi_plugin_reservationdetails_customfields')) {
        if (!$DB->fieldExists('glpi_plugin_reservationdetails_customfields', 'name')) {
            $DB->doQueryOrDie(
                "ALTER TABLE `glpi_plugin_reservationdetails_customfields` 
                 ADD COLUMN `name` VARCHAR(255) DEFAULT NULL,
                 ADD COLUMN `comment` TEXT DEFAULT NULL",
                $DB->error()
            );
            // Copy field_label to name for existing records
            $DB->doQuery("UPDATE `glpi_plugin_reservationdetails_customfields` SET `name` = `field_label` WHERE `name` IS NULL");
        }
    }

    $migration->executeMigration();
    return true;
}

function plugin_reservationdetails_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_reservationdetails_itemtypes_profiles',
        'glpi_plugin_reservationdetails_customfields_values',
        'glpi_plugin_reservationdetails_customfields',
        'glpi_plugin_reservationdetails_resources_reservationsitems',
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

/**
 * Item can hook: Filter ReservationItem list to hide restricted itemtypes
 * This hook is called during Search::addDefaultWhere when add_where is used
 */
function plugin_reservationdetails_item_can(CommonDBTM $item) {
    // Get list of itemtypes that user cannot reserve
    $restrictedItemtypes = ItemPermission::getRestrictedItemtypesForUser();
    
    if (!empty($restrictedItemtypes)) {
        // Build WHERE clause to exclude restricted itemtypes
        $excluded = [];
        foreach ($restrictedItemtypes as $itemtype) {
            $excluded[] = "'" . addslashes($itemtype) . "'";
        }
        $excludeClause = "glpi_reservationitems.itemtype NOT IN (" . implode(',', $excluded) . ")";
        
        // Set add_where to filter search results
        $item->add_where = $excludeClause;
    }
}

/**
 * Pre-add hook: Check permissions BEFORE reservation is created
 * Returns false to block reservation creation
 */
function plugin_reservationdetails_preadditem_called(CommonDBTM $item) {
    if ($item::getType() == \Reservation::class) {
        $reservationItemId = $item->input['reservationitems_id'] ?? 0;
        
        if ($reservationItemId > 0) {
            $reservationItem = new \ReservationItem();
            if ($reservationItem->getFromDB($reservationItemId)) {
                $itemtype = $reservationItem->fields['itemtype'];
                
                if (!ItemPermission::canUserReserveItemtype($itemtype)) {
                    \Session::addMessageAfterRedirect(
                        __('Seu perfil não tem permissão para reservar este tipo de item.'),
                        false,
                        ERROR
                    );
                    // Block creation by setting input to false
                    $item->input = false;
                    return false;
                }
            }
        }
    }
    return true;
}

function plugin_reservationdetails_additem_called(CommonDBTM $item) {
    if ($item::getType() == \Reservation::class) {
        global $DB;
        
        $found = false;
        $resourcesWithTicket = [];
        $allResources = [];

        // Collect all resources from POST
        foreach (array_keys($_POST) as $key) {
            if (strpos($key, 'resource_id_') === 0) {
                $found = true;
                $parts = explode('_', $key);
                $resourceId = (int)$parts[2];
                $allResources[] = $resourceId;
                
                // Check if this resource has ticket_entities_id
                $resource = $DB->request([
                    'SELECT' => ['name', 'ticket_entities_id'],
                    'FROM'   => 'glpi_plugin_reservationdetails_resources',
                    'WHERE'  => ['id' => $resourceId]
                ])->current();
                
                if ($resource && !empty($resource['ticket_entities_id'])) {
                    $resourcesWithTicket[] = [
                        'id' => $resourceId,
                        'name' => $resource['name'],
                        'ticket_entities_id' => $resource['ticket_entities_id']
                    ];
                }
            }
        }

        // Check if this is a bulk/recurring reservation
        $isBulk = false;
        if (isset($_POST['periodicity']) && is_array($_POST['periodicity'])) {
            $isBulk = !empty($_POST['periodicity']['type']);
        }

        if (!$found) {
            // No resources selected - redirect to resource form
            if (!$isBulk) {
                $obj = new Reservation;
                Html::redirect($obj->getFormURLWithID($item->getID()));
            }
        } else {
            $reservationId = $item->fields['id'];
            $ticketId = null;
            
            // Create ONE ticket for the reservation if any resource requires it
            if (!empty($resourcesWithTicket)) {
                $firstResource = $resourcesWithTicket[0];
                $resourceNames = array_map(fn($r) => $r['name'], $resourcesWithTicket);
                
                // Get reservation details
                $reservation = new \Reservation();
                $reservation->getFromDB($reservationId);
                $reservationItem = new \ReservationItem();
                $reservationItem->getFromDB($reservation->fields['reservationitems_id']);
                
                $itemName = '';
                if (!empty($reservationItem->fields['itemtype'])) {
                    $itemClass = $reservationItem->fields['itemtype'];
                    $linkedItem = new $itemClass();
                    if ($linkedItem->getFromDB($reservationItem->fields['items_id'])) {
                        $itemName = $linkedItem->getName();
                    }
                }
                
                $ticket = [
                    'entities_id'     => $firstResource['ticket_entities_id'],
                    'name'            => 'Reserva: ' . $itemName,
                    'content'         => sprintf(
                        "Reserva criada.\n\nLocal: %s\nData: %s até %s\nRecursos: %s",
                        $itemName,
                        $reservation->fields['begin'] ?? '',
                        $reservation->fields['end'] ?? '',
                        implode(', ', $resourceNames)
                    ),
                    'date'            => date('Y-m-d H:i:s'),
                    'requesttypes_id' => 1,
                    'status'          => 1
                ];

                $track = new \Ticket();
                $ticketId = $track->add($ticket);
                
                if ($ticketId) {
                    \Session::addMessageAfterRedirect(
                        sprintf(__("Chamado #%d criado para a reserva."), $ticketId),
                        true,
                        INFO
                    );
                }
            }
            
            // Link each resource to the reservation
            foreach ($allResources as $resourceId) {
                Resource::create($resourceId, $reservationId, $isBulk, $ticketId);
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
        
        // Find associated tickets from resources_reservationsitems
        $tickets = $DB->request([
            'SELECT' => ['id', 'tickets_id'],
            'FROM'   => 'glpi_plugin_reservationdetails_resources_reservationsitems',
            'WHERE'  => [
                'reservations_id' => $reservationId,
                'tickets_id' => ['>', 0]
            ]
        ]);
        
        $solvedTickets = [];
        foreach ($tickets as $row) {
            if (!empty($row['tickets_id']) && !in_array($row['tickets_id'], $solvedTickets)) {
                $ticket = new \Ticket();
                if ($ticket->getFromDB($row['tickets_id'])) {
                    // Solve the ticket
                    $ticket->update([
                        'id'     => $row['tickets_id'],
                        'status' => \CommonITILObject::SOLVED,
                        'solution' => 'Reserva cancelada/excluída pelo sistema.'
                    ]);
                    
                    $solvedTickets[] = $row['tickets_id'];
                    
                    \Session::addMessageAfterRedirect(
                        sprintf(__("Chamado #%d solucionado automaticamente."), $row['tickets_id']),
                        true,
                        INFO
                    );
                }
            }
        }
        
        // Clear the reservation reference from resources_reservationsitems (don't delete - preserve resource link)
        $DB->update('glpi_plugin_reservationdetails_resources_reservationsitems', [
            'reservations_id' => null,
            'tickets_id' => null
        ], [
            'reservations_id' => $reservationId
        ]);
    }
}
