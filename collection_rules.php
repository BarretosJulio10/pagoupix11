<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/CollectionRule.php';
require_once 'includes/Communication.php';

// Initialize models
$collectionRuleModel = new CollectionRule();
$communicationModel = new Communication();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            // Toggle rule status
            if ($_POST['action'] === 'toggle_status' && isset($_POST['rule_id'])) {
                $collectionRuleModel->toggleStatus($_POST['rule_id']);
                $successMessage = 'Status da regra atualizado com sucesso!';
            }
            
            // Delete rule
            if ($_POST['action'] === 'delete' && isset($_POST['rule_id'])) {
                $collectionRuleModel->delete($_POST['rule_id']);
                $successMessage = 'Regra de cobrança excluída com sucesso!';
            }
        } else {
            // Create or update rule
            $data = [
                'template_id' => $_POST['template_id'],
                'days_offset' => $_POST['days_offset'],
                'is_before_due' => isset($_POST['is_before_due']) ? 1 : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            if (empty($_POST['rule_id'])) {
                // Create new rule
                $collectionRuleModel->create($data);
                $successMessage = 'Regra de cobrança criada com sucesso!';
            } else {
                // Update existing rule
                $collectionRuleModel->update($_POST['rule_id'], $data);
                $successMessage = 'Regra de cobrança atualizada com sucesso!';
            }
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Get all rules
$rules = $collectionRuleModel->getAll();

// Get all message templates
$templates = $communicationModel->getTemplates();

// Page title
$pageTitle = 'Régua de Cobrança - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Régua de Cobrança</h1>
            <p class="text-muted">Configure quando e quais mensagens serão enviadas aos clientes</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ruleModal">
                <i class="fas fa-plus"></i> Nova Regra
            </button>
        </div>
    </div>
    
    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Regras Antes do Vencimento</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Dias antes</th>
                            <th>Template</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <?php if ($rule['is_before_due']): ?>
                                <tr>
                                    <td><?php echo $rule['days_offset']; ?> dias</td>
                                    <td><?php echo htmlspecialchars($rule['template_name']); ?></td>
                                    <td><?php echo htmlspecialchars($rule['template_type']); ?></td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $rule['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo $rule['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-rule" 
                                                data-id="<?php echo $rule['id']; ?>"
                                                data-template-id="<?php echo $rule['template_id']; ?>"
                                                data-days-offset="<?php echo $rule['days_offset']; ?>"
                                                data-is-before-due="<?php echo $rule['is_before_due']; ?>"
                                                data-is-active="<?php echo $rule['is_active']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#ruleModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta regra?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!array_filter($rules, function($r) { return $r['is_before_due']; })): ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhuma regra configurada</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Regras Após o Vencimento</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Dias após</th>
                            <th>Template</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <?php if (!$rule['is_before_due']): ?>
                                <tr>
                                    <td><?php echo $rule['days_offset']; ?> dias</td>
                                    <td><?php echo htmlspecialchars($rule['template_name']); ?></td>
                                    <td><?php echo htmlspecialchars($rule['template_type']); ?></td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $rule['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo $rule['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-rule" 
                                                data-id="<?php echo $rule['id']; ?>"
                                                data-template-id="<?php echo $rule['template_id']; ?>"
                                                data-days-offset="<?php echo $rule['days_offset']; ?>"
                                                data-is-before-due="<?php echo $rule['is_before_due']; ?>"
                                                data-is-active="<?php echo $rule['is_active']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#ruleModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta regra?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!array_filter($rules, function($r) { return !$r['is_before_due']; })): ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhuma regra configurada</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rule Modal -->
<div class="modal fade" id="ruleModal" tabindex="-1" aria-labelledby="ruleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="ruleModalLabel">Nova Regra de Cobrança</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="rule_id" id="rule_id">
                    
                    <div class="mb-3">
                        <label for="template_id" class="form-label">Template de Mensagem</label>
                        <select class="form-select" name="template_id" id="template_id" required>
                            <option value="">Selecione um template</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>">
                                    <?php echo htmlspecialchars($template['name']); ?> (<?php echo $template['type']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="days_offset" class="form-label">Dias de Offset</label>
                        <input type="number" class="form-control" name="days_offset" id="days_offset" min="1" required>
                        <div class="form-text">Número de dias antes ou após o vencimento</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_before_due" id="is_before_due" value="1" checked>
                        <label class="form-check-label" for="is_before_due">Antes do vencimento</label>
                        <div class="form-text">Se desmarcado, será considerado após o vencimento</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Regra ativa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit rule modal
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-rule');
        const ruleModal = document.getElementById('ruleModal');
        const modalTitle = ruleModal.querySelector('.modal-title');
        const ruleIdInput = document.getElementById('rule_id');
        const templateIdSelect = document.getElementById('template_id');
        const daysOffsetInput = document.getElementById('days_offset');
        const isBeforeDueCheckbox = document.getElementById('is_before_due');
        const isActiveCheckbox = document.getElementById('is_active');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const templateId = this.getAttribute('data-template-id');
                const daysOffset = this.getAttribute('data-days-offset');
                const isBeforeDue = this.getAttribute('data-is-before-due') === '1';
                const isActive = this.getAttribute('data-is-active') === '1';
                
                modalTitle.textContent = 'Editar Regra de Cobrança';
                ruleIdInput.value = id;
                templateIdSelect.value = templateId;
                daysOffsetInput.value = daysOffset;
                isBeforeDueCheckbox.checked = isBeforeDue;
                isActiveCheckbox.checked = isActive;
            });
        });
        
        // Reset form when opening modal for new rule
        ruleModal.addEventListener('hidden