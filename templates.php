<?php
require_once 'config.php';
require_once 'includes/Database.php';

// Initialize database connection
$db = Database::getInstance();

// Get all message templates
$sql = "SELECT * FROM message_templates ORDER BY name";
$templates = $db->fetchAll($sql);

// Check if there are enough templates of each type
$templateCounts = $db->fetchAll("SELECT type, COUNT(*) as count FROM message_templates WHERE type IN ('payment_reminder', 'payment_confirmation', 'payment_overdue') GROUP BY type");
$templateCountMap = [];
foreach ($templateCounts as $count) {
    $templateCountMap[$count['type']] = $count['count'];
}

$missingTemplates = [];
$requiredTypes = [
    'payment_reminder' => 'Lembrete de Pagamento',
    'payment_confirmation' => 'Confirmação de Pagamento',
    'payment_overdue' => 'Pagamento em Atraso'
];

foreach ($requiredTypes as $type => $label) {
    if (!isset($templateCountMap[$type]) || $templateCountMap[$type] < 1) {
        $missingTemplates[] = $label;
    }
}

// Page title
$pageTitle = 'Templates - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <?php if (!empty($missingTemplates)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Atenção!</strong> O sistema requer pelo menos um template de cada tipo para enviar mensagens automaticamente. 
            Tipos de templates necessários: <?php echo implode(', ', $missingTemplates); ?>.
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Templates de Mensagens</h1>
            <p class="text-muted">Gerenciamento de templates de mensagens</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="template_form.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Novo Template
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (count($templates) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Assunto</th>
                                <th>Tipo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo $template['name']; ?></td>
                                    <td><?php echo $template['subject']; ?></td>
                                    <td>
                                        <?php
                                        $typeLabels = [
                                            'payment_reminder' => 'Lembrete de Pagamento',
                                            'payment_confirmation' => 'Confirmação de Pagamento',
                                            'payment_overdue' => 'Pagamento em Atraso',
                                            'welcome' => 'Boas-vindas'
                                        ];
                                        echo $typeLabels[$template['type']] ?? $template['type'];
                                        ?>
                                    </td>
                                    <td>
                                        <a href="template_view.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="template_form.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-template" data-id="<?php echo $template['id']; ?>" data-name="<?php echo htmlspecialchars($template['name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Nenhum template cadastrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Template Confirmation Modal -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTemplateModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o template <strong id="template-name-to-delete"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-template">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle delete template button click
        const deleteButtons = document.querySelectorAll('.delete-template');
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteTemplateModal'));
        const templateNameElement = document.getElementById('template-name-to-delete');
        const confirmDeleteButton = document.getElementById('confirm-delete-template');
        let templateIdToDelete = null;
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                templateIdToDelete = this.getAttribute('data-id');
                templateNameElement.textContent = this.getAttribute('data-name');
                deleteModal.show();
            });
        });
        
        confirmDeleteButton.addEventListener('click', function() {
            if (templateIdToDelete) {
                // Create form data
                const formData = new FormData();
                formData.append('template_id', templateIdToDelete);
                
                // Send AJAX request
                fetch('delete_template.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    deleteModal.hide();
                    
                    if (data.success) {
                        // Reload page on success
                        location.reload();
                    } else {
                        alert('Erro ao excluir template: ' + (data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    deleteModal.hide();
                    alert('Erro ao excluir template: ' + error);
                });
            }
        });
    });
</script>

<?php include 'templates/footer.php'; ?>