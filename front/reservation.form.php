<?php

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use GlpiPlugin\Reservationdetails\Entity\Resource;
use GlpiPlugin\Reservationdetails\Entity\CustomFieldValue;

include("../../../inc/includes.php");

$plugin = new Plugin();
$obj = new Reservation();

if (!$plugin->isInstalled('reservationdetails') || !$plugin->isActivated('reservationdetails')) {
    Session::addMessageAfterRedirect(__('Plugin not activated'), false, ERROR);

    Html::redirect($CFG_GLPI['root_doc'] . '/front/central.php');
}

Session::checkLoginUser();

if (isset($_POST['add'])) {
    global $DB;
    
    $_POST['reservations_id'] = $_GET['id'];
    $reservationId = $_GET['id'];

    // Collect resources that need tickets
    $resourcesWithTicket = [];
    $allResourceKeys = [];
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'resource_id_') !== false) {
            $allResourceKeys[$key] = $value;
            $resourceId = (int)$value;
            
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

    // Create ONE ticket for the reservation if any resource requires it
    $ticketId = null;
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
            Session::addMessageAfterRedirect(
                sprintf(__("Chamado #%d criado para a reserva."), $ticketId),
                true,
                INFO
            );
        }
    }

    // Process resources with ticketId
    foreach ($allResourceKeys as $key => $resourceValue) {
        if (!Resource::create((int)$resourceValue, $reservationId, false, $ticketId)) {
            Session::addMessageAfterRedirect(
                __('Resource not available for this date'),
                false,
                ERROR
            );
        }
    }

    // Save custom field values (text-based fields)
    $customFieldValues = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'customfield_') === 0 && strpos($key, 'customfield_file_') !== 0) {
            $fieldId = (int) str_replace('customfield_', '', $key);
            if ($fieldId > 0 && !empty($value)) {
                $customFieldValues[$fieldId] = $value;
            }
        }
    }
    
    // Handle file uploads
    if (!empty($_FILES)) {
        foreach ($_FILES as $key => $fileData) {
            if (strpos($key, 'customfield_file_') === 0 && !empty($fileData['name'])) {
                $fieldId = (int) str_replace('customfield_file_', '', $key);
                if ($fieldId > 0 && $fileData['error'] === UPLOAD_ERR_OK) {
                    // Generate unique filename to avoid conflicts
                    $uniquePrefix = uniqid() . '_';
                    $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileData['name']);
                    $targetFilename = $uniquePrefix . $safeFilename;
                    $targetPath = GLPI_UPLOAD_DIR . '/' . $targetFilename;
                    
                    // Move uploaded file to GLPI temp directory
                    if (move_uploaded_file($fileData['tmp_name'], $targetPath)) {
                        // Create document using GLPI Document system
                        $document = new \Document();
                        
                        $docData = [
                            'entities_id' => $_SESSION['glpiactive_entity'],
                            'name' => sprintf('Reserva #%d - %s', $reservationId, $fileData['name']),
                            'documentcategories_id' => 0,
                            'users_id' => Session::getLoginUserID(),
                            'upload_file' => $targetFilename
                        ];
                        
                        $docId = $document->add($docData);
                        
                        if ($docId) {
                            // Store document ID as the field value
                            $customFieldValues[$fieldId] = 'document:' . $docId;
                            Session::addMessageAfterRedirect(
                                sprintf(__('Arquivo "%s" enviado com sucesso.'), $fileData['name']),
                                true,
                                INFO
                            );
                        } else {
                            Session::addMessageAfterRedirect(
                                sprintf(__('Erro ao fazer upload do arquivo: %s'), $fileData['name']),
                                false,
                                ERROR
                            );
                            // Clean up failed upload
                            if (file_exists($targetPath)) {
                                unlink($targetPath);
                            }
                        }
                    } else {
                        Session::addMessageAfterRedirect(
                            sprintf(__('Erro ao mover arquivo: %s'), $fileData['name']),
                            false,
                            ERROR
                        );
                    }
                }
            }
        }
    }
    
    if (!empty($customFieldValues)) {
        CustomFieldValue::saveForReservation($_GET['id'], $customFieldValues);
    }

    $ri = new \ReservationItem();
    $ri->redirectToList();
} else if (isset($_POST["update"])) {
    Html::redirect($obj->getLinkURL());
} else {
    $withtemplate = isset($_GET['withtemplate']) ? $_GET['withtemplate'] : "";
    $id = -1;
    Reservation::displayFullPageForItem($id, null, [
        'idReservation' => $_GET['id']
    ]);
}
