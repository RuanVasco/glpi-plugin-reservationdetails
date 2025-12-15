<?php

use GlpiPlugin\Reservationdetails\Entity\Resource;

define('PLUGIN_RESERVATIONDETAILS_VERSION', '0.1.0');

function plugin_init_reservationdetails() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['reservationdetails'] = true;
    $PLUGIN_HOOKS['use_massive_action']['fillglpi'] = 1;
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
