<?php

use GlpiPlugin\Reservationdetails\Entity\Resource;
use GlpiPlugin\Reservationdetails\Entity\Profile;
use GlpiPlugin\Reservationdetails\Entity\ReservationView;

define('PLUGIN_RESERVATIONDETAILS_VERSION', '0.1.0');

function plugin_init_reservationdetails() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['reservationdetails'] = true;
    $PLUGIN_HOOKS['use_massive_action']['fillglpi'] = 1;

    $PLUGIN_HOOKS['item_add']['reservationdetails'] = [
        'Reservation' => 'plugin_reservationdetails_additem_called'
    ];

    $PLUGIN_HOOKS['post_item_form']['reservationdetails'] = [
        'Reservation' => 'plugin_reservationdetails_params_hook'
    ];

    // Register profile tab
    Plugin::registerClass(Profile::class, ['addtabon' => ['Profile']]);

    // Register reservations view tab on ReservationItem
    Plugin::registerClass(ReservationView::class, ['addtabon' => ['ReservationItem']]);

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
    return [Resource::class => Resource::getTypeName(2)];
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
