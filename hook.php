<?php

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use GlpiPlugin\Reservationdetails\Entity\Resource;

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

    $migration->executeMigration();
    return true;
}

function plugin_reservationdetails_uninstall() {
    global $DB;

    $tables = [
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

    return true;
}

function reservationdetails_additem_called(CommonDBTM $item) {
    if ($item::getType() == \Reservation::class) {
        $obj = new Reservation;
        $found = false;

        foreach (array_keys($_POST) as $key) {
            if (strpos($key, 'resource_id_') === 0) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            Html::redirect($obj->getFormURLWithID($item->getID()));
        } else {
            $_POST['reservations_id'] = $item->fields['id'];

            foreach ($_POST as $i => $key) {
                if (strpos($i, 'resource_id_') !== false) {
                    $parts = explode('_', $i);

                    $resourceID = $parts[2];

                    Resource::create($resourceID, $_POST['reservations_id']);
                }
            }

            $obj->check(-1, CREATE, $_POST);
            $obj->add($_POST);
        }
    }
}

function reservationdetails_params_hook(array $params) {
    if (($params['item'] == new \Reservation())) {
        Reservation::addFieldsInReservationForm();
    }
}


function plugin_fillglpi_getDropdown() {
    return [
        Resource::class     => _n('Recurso para Reserva', 'Recursos para Reserva', 2, 'fillglpi')
    ];
}
