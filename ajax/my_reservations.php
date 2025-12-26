<?php
/**
 * AJAX endpoint for loading user's reservations in helpdesk interface
 * Matching admin view with modal and resources
 */

use GlpiPlugin\Reservationdetails\Repository\ReservationRepository;
use GlpiPlugin\Reservationdetails\Utils;

include("../../../inc/includes.php");

Session::checkLoginUser();

global $DB;

$userId = Session::getLoginUserID();
$status = isset($_GET['status']) ? $_GET['status'] : 'open';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

$reservationRepository = new ReservationRepository($DB);
$reservations = $reservationRepository->getReservationsList($status, $page, $perPage, 'begin', 'DESC', '', $userId);
$totalCount = $reservationRepository->getReservationsCount($status, '', $userId);
$totalPages = max(1, ceil($totalCount / $perPage));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Minhas Reservas</h4>
    <div class="btn-group" role="group">
        <button type="button" class="btn <?php echo $status === 'open' ? 'btn-primary' : 'btn-outline-primary'; ?>" onclick="loadMyReservationsWithStatus('open')">
            Em aberto
        </button>
        <button type="button" class="btn <?php echo $status === 'closed' ? 'btn-secondary' : 'btn-outline-secondary'; ?>" onclick="loadMyReservationsWithStatus('closed')">
            Fechadas
        </button>
    </div>
</div>

<?php if (empty($reservations)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Nenhuma reserva <?php echo $status === 'open' ? 'em aberto' : 'fechada'; ?> encontrada.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover text-center">
            <thead class="table-light">
                <tr>
                    <th>Ver</th>
                    <th>Item</th>
                    <th>Início</th>
                    <th>Fim</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                <tr>
                    <td>
                        <button 
                            type="button"
                            class="btn btn-primary btn-sm"
                            data-reservation-id="<?php echo $res['id']; ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#helpdeskReservationModal"
                            onclick="showHelpdeskReservationModal(<?php echo $res['id']; ?>)"
                        ><i class="fas fa-eye"></i></button>
                    </td>
                    <td><strong><?php echo htmlspecialchars($res['item']); ?></strong></td>
                    <td><?php echo Utils::formatToBr($res['begin']); ?></td>
                    <td><?php echo Utils::formatToBr($res['end']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav aria-label="Paginação">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="#" onclick="loadMyReservationsPage(<?php echo $page - 1; ?>); return false;">Anterior</a>
            </li>
            <li class="page-item disabled">
                <span class="page-link">Página <?php echo $page; ?> de <?php echo $totalPages; ?> (<?php echo $totalCount; ?> registros)</span>
            </li>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="#" onclick="loadMyReservationsPage(<?php echo $page + 1; ?>); return false;">Próxima</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal for reservation details -->
<div class="modal fade" id="helpdeskReservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>Detalhes da Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="helpdeskModalBody">
                <div class="text-center p-3">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Store current status for pagination
window._currentReservationStatus = '<?php echo $status; ?>';

function showHelpdeskReservationModal(reservationId) {
    const modalBody = document.getElementById('helpdeskModalBody');
    modalBody.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>';
    
    const rootDoc = typeof CFG_GLPI !== 'undefined' ? CFG_GLPI.root_doc : '';
    
    fetch(rootDoc + '/plugins/reservationdetails/ajax/reservations.php?id=' + reservationId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                modalBody.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                return;
            }
            
            let html = '<table class="table table-borderless">';
            html += '<tr><td class="fw-bold" style="width: 30%">Usuário:</td><td>' + (data.user || '-') + '</td></tr>';
            html += '<tr><td class="fw-bold">Item:</td><td>' + (data.itemName || '-') + '</td></tr>';
            html += '<tr><td class="fw-bold">Início:</td><td>' + (data.begin || '-') + '</td></tr>';
            html += '<tr><td class="fw-bold">Fim:</td><td>' + (data.end || '-') + '</td></tr>';
            html += '<tr><td class="fw-bold">Comentário:</td><td>' + (data.comment || '-') + '</td></tr>';
            
            // Resources
            if (data.recursos && data.recursos.length > 0) {
                html += '<tr><td class="fw-bold align-top">Recursos:</td><td><ul class="mb-0">';
                data.recursos.forEach(recurso => {
                    html += '<li>' + recurso.name + '</li>';
                });
                html += '</ul></td></tr>';
            }
            
            // Custom Fields
            if (data.customFields && data.customFields.length > 0) {
                data.customFields.forEach(field => {
                    let valueHtml = field.value || '-';
                    
                    if (field.isDocument) {
                        if (field.isImage) {
                            valueHtml = '<a href="' + field.documentUrl + '" target="_blank">' +
                                '<img src="' + field.documentUrl + '" alt="' + field.value + '" ' +
                                'style="max-width: 200px; max-height: 150px; border-radius: 4px;"/></a>' +
                                '<br><small><a href="' + field.documentUrl + '" target="_blank">' + field.value + '</a></small>';
                        } else {
                            valueHtml = '<a href="' + field.documentUrl + '" target="_blank">' +
                                '<i class="fas fa-file-download me-1"></i>' + field.value + '</a>';
                        }
                    }
                    
                    html += '<tr><td class="fw-bold align-top">' + field.label + ':</td><td>' + valueHtml + '</td></tr>';
                });
            }
            
            html += '</table>';
            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes: ' + error.message + '</div>';
        });
}

// Override page function to use stored status
window.loadMyReservationsPage = function(page) {
    const status = window._currentReservationStatus || 'open';
    window.loadMyReservations(status, page);
};
</script>
