<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Client.php';
require_once 'includes/Subscription.php';

// Initialize models
$clientModel = new Client();
$subscriptionModel = new Subscription();

// Get all clients for select box
$clients = $clientModel->getAll();

// Check if there are enough templates of each type
$db = Database::getInstance();
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

// Check if editing an existing subscription
$subscriptionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$subscription = [];
$isEdit = false;

// Get client_id from URL if provided
$selectedClientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;

// Initialize error array
$errors = [];

if ($subscriptionId > 0) {
    $subscription = $subscriptionModel->getById($subscriptionId);
    $isEdit = true;
    
    if (!$subscription) {
        // Redirect to subscriptions list if subscription not found
        header('Location: subscriptions.php');
        exit;
    }
    $selectedClientId = $subscription['client_id'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'client_id' => $_POST['client_id'] ?? '',
            'amount' => $_POST['amount'] ?? '',
            'due_date' => $_POST['due_date'] ?? '',
            'frequency' => $_POST['frequency'] ?? 'monthly',
            'interest_type' => $_POST['interest_type'] ?? 'system_default',
            'interest_value' => $_POST['interest_value'] ?? null,
            'interest_enabled' => isset($_POST['interest_enabled']) ? 1 : 0,
            'penalty_type' => $_POST['penalty_type'] ?? 'system_default',
            'penalty_value' => $_POST['penalty_value'] ?? null,
            'penalty_enabled' => isset($_POST['penalty_enabled']) ? 1 : 0
        ];

        // Validate due date
        if (strtotime($data['due_date']) < strtotime(date('Y-m-d'))) {
            $errors['due_date'] = 'A data de vencimento não pode ser anterior à data atual';
            throw new Exception('Validation failed');
        }
        
        // Start transaction
        $db = Database::getInstance();
        $db->beginTransaction();
        
        try {
            if ($isEdit) {
                // Update existing subscription
                $subscriptionModel->update($subscriptionId, $data);
                
                // Delete existing template associations
                $db->delete('client_templates', 'client_id = ?', [$data['client_id']]);
                
                $_SESSION['flash_message'] = 'Mensalidade atualizada com sucesso!';
            } else {
                // Create new subscription
                $subscriptionModel->create($data);
                $_SESSION['flash_message'] = 'Mensalidade criada com sucesso!';
            }
            
            // Insert new template associations for each template type
            $templateTypes = ['payment_reminder', 'payment_confirmation', 'payment_overdue'];
            
            foreach ($templateTypes as $type) {
                $fieldName = 'template_' . $type;
                if (isset($_POST[$fieldName]) && !empty($_POST[$fieldName])) {
                    $templateId = (int)$_POST[$fieldName];
                    $db->insert('client_templates', [
                        'client_id' => $data['client_id'],
                        'template_id' => $templateId
                    ]);
                }
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
        $_SESSION['flash_type'] = 'success';
        
        // Redirect to subscriptions list
        header('Location: subscriptions.php');
        exit;
    } catch (Exception $e) {
        if (!isset($errors['due_date'])) {
            $_SESSION['flash_message'] = 'Erro: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
    }
}

// Page title
$pageTitle = ($isEdit ? 'Editar' : 'Nova') . ' Mensalidade - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <?php if (!empty($missingTemplates)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Atenção!</strong> O sistema não poderá enviar mensagens automaticamente porque não há templates cadastrados para: 
            <?php echo implode(', ', $missingTemplates); ?>.
            <a href="template_form.php" class="alert-link">Clique aqui para criar os templates necessários</a>.
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><?php echo $isEdit ? 'Editar' : 'Nova'; ?> Mensalidade</h1>
            <p class="text-muted">Preencha os dados da mensalidade</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="client_id" class="form-label">Cliente *</label>
                    <select class="form-control" id="client_id" name="client_id" required>
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo ($selectedClientId ?? '') == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo $client['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">Valor *</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : (isset($subscription['amount']) ? $subscription['amount'] : ''); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="due_date" class="form-label">Data de Vencimento *</label>
                    <input type="date" class="form-control <?php echo isset($errors['due_date']) ? 'is-invalid' : ''; ?>" id="due_date" name="due_date" required value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : (isset($subscription['due_date']) ? $subscription['due_date'] : ''); ?>">
                    <?php if (isset($errors['due_date'])): ?>
                        <div class="invalid-feedback">
                            <?php echo $errors['due_date']; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="frequency" class="form-label">Frequência *</label>
                    <select class="form-control" id="frequency" name="frequency" required>
                        <option value="daily" <?php echo ($subscription['frequency'] ?? 'monthly') == 'daily' ? 'selected' : ''; ?>>Diária</option>
                        <option value="weekly" <?php echo ($subscription['frequency'] ?? 'monthly') == 'weekly' ? 'selected' : ''; ?>>Semanal</option>
                        <option value="biweekly" <?php echo ($subscription['frequency'] ?? 'monthly') == 'biweekly' ? 'selected' : ''; ?>>Quinzenal</option>
                        <option value="monthly" <?php echo ($subscription['frequency'] ?? 'monthly') == 'monthly' ? 'selected' : ''; ?>>Mensal</option>
                        <option value="quarterly" <?php echo ($subscription['frequency'] ?? 'monthly') == 'quarterly' ? 'selected' : ''; ?>>Trimestral</option>
                        <option value="semiannual" <?php echo ($subscription['frequency'] ?? 'monthly') == 'semiannual' ? 'selected' : ''; ?>>Semestral</option>
                        <option value="annual" <?php echo ($subscription['frequency'] ?? 'monthly') == 'annual' ? 'selected' : ''; ?>>Anual</option>
                    </select>
                </div>

                <hr>
                <h4>Templates de Mensagens</h4>
                <p class="text-muted small">Selecione os templates que serão utilizados para este cliente</p>
                
                <?php
                // Buscar todos os templates disponíveis
                $db = Database::getInstance();
                $templates = $db->fetchAll("SELECT * FROM message_templates WHERE type IN ('payment_reminder', 'payment_confirmation', 'payment_overdue') ORDER BY type, name");
                
                // Buscar templates já selecionados para este cliente (se estiver editando)
                $selectedTemplates = [];
                if ($isEdit && isset($subscription['client_id'])) {
                    $clientTemplates = $db->fetchAll("SELECT template_id FROM client_templates WHERE client_id = ?", [$subscription['client_id']]);
                    foreach ($clientTemplates as $ct) {
                        $selectedTemplates[] = $ct['template_id'];
                    }
                }

                // Agrupar templates por tipo
                $groupedTemplates = [];
                foreach ($templates as $template) {
                    $groupedTemplates[$template['type']][] = $template;
                }

                $typeLabels = [
                    'payment_reminder' => 'Lembrete de Pagamento',
                    'payment_confirmation' => 'Confirmação de Pagamento',
                    'payment_overdue' => 'Pagamento em Atraso'
                ];
                ?>
                
                <?php foreach ($typeLabels as $type => $label): ?>
                    <div class="mb-4">
                        <label for="template_<?php echo $type; ?>" class="form-label"><?php echo $label; ?></label>
                        <select class="form-select" id="template_<?php echo $type; ?>" name="template_<?php echo $type; ?>">
                            <option value="">Selecione um template</option>
                            <?php if (isset($groupedTemplates[$type])): ?>
                                <?php foreach ($groupedTemplates[$type] as $template): ?>
                                    <option value="<?php echo $template['id']; ?>" 
                                            <?php echo in_array($template['id'], $selectedTemplates) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($template['subject']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (!isset($groupedTemplates[$type]) || empty($groupedTemplates[$type])): ?>
                            <div class="form-text text-muted">Nenhum template disponível para este tipo de mensagem.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <hr>
                
                <hr>
                <h4>Configurações de Juros e Multa</h4>
                <p class="text-muted small">Configure juros e multa específicos para esta mensalidade ou use as configurações padrão do sistema</p>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Juros</h5>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="interest_enabled" id="interest_enabled" <?php echo ($subscription && isset($subscription['interest_enabled']) && $subscription['interest_enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="interest_enabled">
                                Ativar cobrança de juros
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="interest_type" class="form-label">Tipo de Juros</label>
                            <select class="form-select" id="interest_type" name="interest_type">
                                <option value="system_default" <?php echo (!$subscription || !isset($subscription['interest_type']) || $subscription['interest_type'] === 'system_default') ? 'selected' : ''; ?>>Usar configuração do sistema</option>
                                <option value="percentage" <?php echo ($subscription && isset($subscription['interest_type']) && $subscription['interest_type'] === 'percentage') ? 'selected' : ''; ?>>Porcentagem ao dia</option>
                                <option value="daily_value" <?php echo ($subscription && isset($subscription['interest_type']) && $subscription['interest_type'] === 'daily_value') ? 'selected' : ''; ?>>Valor fixo diário</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="interest_value" class="form-label">Valor dos Juros</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="interest_value" name="interest_value" step="0.01" min="0" value="<?php echo ($subscription && isset($subscription['interest_value'])) ? $subscription['interest_value'] : ''; ?>">
                                <span class="input-group-text" id="interest_unit">%</span>
                            </div>
                            <small class="form-text text-muted">Deixe em branco para usar a configuração do sistema</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Multa</h5>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="penalty_enabled" id="penalty_enabled" <?php echo ($subscription && isset($subscription['penalty_enabled']) && $subscription['penalty_enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="penalty_enabled">
                                Ativar cobrança de multa
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="penalty_type" class="form-label">Tipo de Multa</label>
                            <select class="form-select" id="penalty_type" name="penalty_type">
                                <option value="system_default" <?php echo (!$subscription || !isset($subscription['penalty_type']) || $subscription['penalty_type'] === 'system_default') ? 'selected' : ''; ?>>Usar configuração do sistema</option>
                                <option value="percentage" <?php echo ($subscription && isset($subscription['penalty_type']) && $subscription['penalty_type'] === 'percentage') ? 'selected' : ''; ?>>Porcentagem</option>
                                <option value="fixed_value" <?php echo ($subscription && isset($subscription['penalty_type']) && $subscription['penalty_type'] === 'fixed_value') ? 'selected' : ''; ?>>Valor fixo</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="penalty_value" class="form-label">Valor da Multa</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="penalty_value" name="penalty_value" step="0.01" min="0" value="<?php echo ($subscription && isset($subscription['penalty_value'])) ? $subscription['penalty_value'] : ''; ?>">
                                <span class="input-group-text" id="penalty_unit">%</span>
                            </div>
                            <small class="form-text text-muted">Deixe em branco para usar a configuração do sistema</small>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="subscriptions.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
    // Function to update interest unit symbol based on selected interest type
    function updateInterestUnitSymbol() {
        const interestType = document.getElementById('interest_type').value;
        const interestUnitElement = document.getElementById('interest_unit');
        
        if (interestType === 'daily_value') {
            interestUnitElement.textContent = 'R$';
        } else {
            interestUnitElement.textContent = '%';
        }
    }
    
    // Function to update penalty unit symbol based on selected penalty type
    function updatePenaltyUnitSymbol() {
        const penaltyType = document.getElementById('penalty_type').value;
        const penaltyUnitElement = document.getElementById('penalty_unit');
        
        if (penaltyType === 'fixed_value') {
            penaltyUnitElement.textContent = 'R$';
        } else {
            penaltyUnitElement.textContent = '%';
        }
    }
    
    // Add event listeners to type selects
    document.getElementById('interest_type').addEventListener('change', updateInterestUnitSymbol);
    document.getElementById('penalty_type').addEventListener('change', updatePenaltyUnitSymbol);
    
    // Initialize the symbols on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateInterestUnitSymbol();
        updatePenaltyUnitSymbol();
    });
</script>