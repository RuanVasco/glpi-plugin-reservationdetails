<?php

use GlpiPlugin\Reservationdetails\Entity\Resource;
use GlpiPlugin\Reservationdetails\Entity\Profile;
use GlpiPlugin\Reservationdetails\Entity\ReservationView;
use GlpiPlugin\Reservationdetails\Entity\CustomField;
use GlpiPlugin\Reservationdetails\Entity\ItemPermission;

define('PLUGIN_RESERVATIONDETAILS_VERSION', '0.1.0');
define('PLUGIN_RESERVATIONDETAILS_WEBDIR', Plugin::getWebDir('reservationdetails', true));

function plugin_init_reservationdetails() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['reservationdetails'] = true;
    $PLUGIN_HOOKS['use_massive_action']['fillglpi'] = 1;

    $PLUGIN_HOOKS['item_add']['reservationdetails'] = [
        'Reservation' => 'plugin_reservationdetails_additem_called'
    ];

    // Block reservation creation if user doesn't have permission
    $PLUGIN_HOOKS['pre_item_add']['reservationdetails'] = [
        'Reservation' => 'plugin_reservationdetails_preadditem_called'
    ];

    $PLUGIN_HOOKS['item_purge']['reservationdetails'] = [
        'Reservation' => 'plugin_reservationdetails_purgeitem_called'
    ];

    $PLUGIN_HOOKS['post_item_form']['reservationdetails'] = [
        'Reservation' => 'plugin_reservationdetails_params_hook'
    ];

    // Register profile tab
    Plugin::registerClass(Profile::class, ['addtabon' => ['Profile']]);

    // Register reservations view tab on ReservationItem
    Plugin::registerClass(ReservationView::class, ['addtabon' => ['ReservationItem']]);

    // ItemPermission is accessible via dropdown menu

    // Reload rights when profile changes
    $PLUGIN_HOOKS['change_profile']['reservationdetails'] = 'plugin_reservationdetails_changeprofile';
}

function plugin_version_reservationdetails() {
    return [
        'name'           => 'Reservation Details',
        'version'        => PLUGIN_RESERVATIONDETAILS_VERSION,
        'author'         => 'Ruan Vasconcelos',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/RuanVasco',
        'requirements'   => [
            'glpi'   => [
                'min' => '11.0.4',
                'max' => '11.0.5'
            ]
        ],
        'description'   => 'Adds contextual fields to the asset reservation form.'
    ];
}

function plugin_reservationdetails_check_prerequisites() {
    return true;
}

function plugin_reservationdetails_getDropdown() {
    return [
        'GlpiPlugin\\Reservationdetails\\Entity\\Resource' => 'Reservation Details > ' . Resource::getTypeName(2),
        'GlpiPlugin\\Reservationdetails\\Entity\\CustomField' => 'Reservation Details > ' . CustomField::getTypeName(2),
        'GlpiPlugin\\Reservationdetails\\Entity\\ItemPermission' => 'Reservation Details > ' . ItemPermission::getTypeName(2)
    ];
}

function plugin_reservationdetails_changeprofile() {
    // Refresh rights when profile changes
    $rights = Profile::getAllRights();
    foreach ($rights as $right) {
        if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            // Rights are already loaded
        }
    }
}
