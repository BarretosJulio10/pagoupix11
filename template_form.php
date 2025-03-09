<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Communication.php';

// Initialize communication model
$communicationModel = new Communication();

// Initialize templates array
$templates = [
    'payment_reminder' => [],
    'payment_overdue' => [],
    'payment_confirmation' => []
];

// Check if editing an existing template
$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = false;

if ($templateId > 0) {
    $template = $communicationModel->getTemplateById($templateId);
    $isEdit = true;
    
    if (!$template) {
        // Redirect to templates list if template not found
        header('Location: templates.php');
        exit;
    }
    
    // If editing a single template, populate the corresponding type
    if (isset($template['type']) && array_key_exists($template['type'], $templates)) {
        $templates[$template['type']] = $template;
    }
} else {
    // Check if we already have templates of each type
    $db = Database::getInstance();
    $existingTemplates = $db->fetchAll("SELECT * FROM message_templates WHERE type IN ('payment_reminder', 'payment_overdue', 'payment_confirmation')");
    
    foreach ($existingTemplates as $existingTemplate) {
        if (array_key_exists($existingTemplate['type'], $templates)) {
            $templates[$existingTemplate['type']] = $existingTemplate;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $db->beginTransaction();
        
        // Process each template type
        foreach (['payment_reminder', 'payment_overdue', 'payment_confirmation'] as $type) {
            // Check if required fields are provided
            if (empty($_POST["name_$type"]) || empty($_POST["subject_$type"]) || empty($_POST["body_$type"])) {
                throw new Exception("Todos os campos do template de " . getTypeLabel($type) . " são obrigatórios.");
            }
            
            // For payment_reminder and payment_overdue, check if payment_link is included
            if (($type == 'payment_reminder' || $type == 'payment_overdue') && 
                strpos($_POST["body_$type"], '{payment_link}') === false) {
                throw new Exception("O template de " . getTypeLabel($type) . " deve incluir o link de pagamento {payment_link}.");
            }
            
            $data = [
                'name' => $_POST["name_$type"] ?? '',
                'subject' => $_POST["subject_$type"] ?? '',
                'body' => $_POST["body_$type"] ?? '',
                'type' => $type,
                'company_id' => $_POST["company_id_$type"] ?? NULL
            ];
            
            // Handle image upload
            if (isset($_FILES["image_$type"]) && $_FILES["image_$type"]['error'] === UPLOAD_ERR_OK) {
                $imageFile = $_FILES["image_$type"];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!in_array($imageFile['type'], $allowedTypes)) {
                    throw new Exception('Tipo de arquivo inválido. Apenas imagens JPG, PNG e GIF são permitidas.');
                }
                
                $maxSize = 5 * 1024 * 1024; // 5MB
                if ($imageFile['size'] > $maxSize) {
                    throw new Exception('O arquivo é muito grande. Tamanho máximo permitido: 5MB');
                }
                
                $fileName = uniqid() . '_' . basename($imageFile['name']);
                $uploadPath = 'assets/template_images/' . $fileName;
                
                if (move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
                    $data['image_path'] = $uploadPath;
                    
                    // Remove old image if exists
                    if (!empty($templates[$type]['image_path']) && file_exists($templates[$type]['image_path'])) {
                        unlink($templates[$type]['image_path']);
                    }
                } else {
                    throw new Exception('Erro ao fazer upload da imagem');
                }
            } elseif (!empty($templates[$type]['image_path'])) {
                // Keep existing image if no new one is uploaded
                $data['image_path'] = $templates[$type]['image_path'];
            }
            
            if (!empty($templates[$type]['id'])) {
                // Update existing template
                $communicationModel->updateTemplate($templates[$type]['id'], $data);
            } else {
                // Insert new template
                $db->insert('message_templates', $data);
            }
        }
        
        $db->commit();
        $_SESSION['flash_message'] = 'Templates criados com sucesso!';
        $_SESSION['flash_type'] = 'success';
        header('Location: templates.php');
        exit;
    } catch (Exception $e) {
        if (isset($db)) $db->rollback();
        $_SESSION['flash_message'] = 'Erro: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// Helper function to get readable type label
function getTypeLabel($type) {
    $labels = [
        'payment_reminder' => 'Lembrete de Pagamento',
        'payment_overdue' => 'Pagamento em Atraso',
        'payment_confirmation' => 'Confirmação de Pagamento'
    ];
    return $labels[$type] ?? $type;
}

// Page title
$pageTitle = 'Templates de Mensagens - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Templates de Mensagens</h1>
            <p class="text-muted">Crie os templates necessários para comunicação com os clientes</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="templates.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="templateTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="payment-reminder-tab" data-bs-toggle="tab" data-bs-target="#payment-reminder" type="button" role="tab" aria-controls="payment-reminder" aria-selected="true">
                        <i class="fas fa-bell"></i> Lembrete de Pagamento
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-overdue-tab" data-bs-toggle="tab" data-bs-target="#payment-overdue" type="button" role="tab" aria-controls="payment-overdue" aria-selected="false">
                        <i class="fas fa-exclamation-triangle"></i> Pagamento em Atraso
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-confirmation-tab" data-bs-toggle="tab" data-bs-target="#payment-confirmation" type="button" role="tab" aria-controls="payment-confirmation" aria-selected="false">
                        <i class="fas fa-check-circle"></i> Confirmação de Pagamento
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form method="post" action="" enctype="multipart/form-data" id="templateForm">
                <div class="tab-content" id="templateTabsContent">
                    <?php foreach (['payment_reminder', 'payment_overdue', 'payment_confirmation'] as $index => $type): ?>
                        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" id="<?php echo str_replace('_', '-', $type); ?>" role="tabpanel" aria-labelledby="<?php echo str_replace('_', '-', $type); ?>-tab">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name_<?php echo $type; ?>" class="form-label">Nome do Template</label>
                                    <input type="text" class="form-control" id="name_<?php echo $type; ?>" name="name_<?php echo $type; ?>" value="<?php echo htmlspecialchars($templates[$type]['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="subject_<?php echo $type; ?>" class="form-label">Assunto</label>
                                    <input type="text" class="form-control" id="subject_<?php echo $type; ?>" name="subject_<?php echo $type; ?>" value="<?php echo htmlspecialchars($templates[$type]['subject'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="body_<?php echo $type; ?>" class="form-label">Corpo da Mensagem</label>
                                    <textarea class="form-control" id="body_<?php echo $type; ?>" name="body_<?php echo $type; ?>" rows="8" required><?php echo htmlspecialchars($templates[$type]['body'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        Você pode usar os seguintes placeholders:<br>
                                        {client_name} - Nome do cliente<br>
                                        {amount} - Valor da mensalidade/pagamento<br>
                                        {due_date} - Data de vencimento<br>
                                        <?php if ($type == 'payment_reminder' || $type == 'payment_overdue'): ?>
                                            <strong>{payment_link} - Link de pagamento (obrigatório)</strong><br>
                                        <?php else: ?>
                                            {payment_link} - Link de pagamento<br>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="image_<?php echo $type; ?>" class="form-label">Imagem (opcional)</label>
                                    <input type="file" class="form-control" id="image_<?php echo $type; ?>" name="image_<?php echo $type; ?>" accept="image/jpeg,image/png,image/gif">
                                    <?php if (!empty($templates[$type]['image_path'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($templates[$type]['image_path']); ?>" alt="Template Image" class="img-thumbnail" style="max-height: 150px">
                                        <p class="text-muted small">Envie uma nova imagem para substituir a atual</p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="form-text small">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB</div>
                                </div>
                            </div>
                            
                            <?php if ($index < 2): ?>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-secondary" onclick="previousTab()" <?php echo $index === 0 ? 'disabled' : ''; ?>>Anterior</button>
                                    <button type="button" class="btn btn-primary" onclick="nextTab()">Próximo</button>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-secondary" onclick="previousTab()">Anterior</button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Salvar Todos os Templates
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
function nextTab() {
    const activeTab = document.querySelector('.nav-link.active');
    const nextTab = activeTab.closest('.nav-item').nextElementSibling.querySelector('.nav-link');
    const tab = new bootstrap.Tab(nextTab);
    tab.show();
}

function previousTab() {
    const activeTab = document.querySelector('.nav-link.active');
    const prevTab = activeTab.closest('.nav-item').previousElementSibling.querySelector('.nav-link');
    const tab = new bootstrap.Tab(prevTab);
    tab.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('templateForm');
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validate all required fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Validate payment link in reminder and overdue templates
        const reminderBody = document.getElementById('body_payment_reminder').value;
        const overdueBody = document.getElementById('body_payment_overdue').value;
        
        if (reminderBody.indexOf('{payment_link}') === -1) {
            document.getElementById('body_payment_reminder').classList.add('is-invalid');
            alert('O template de Lembrete de Pagamento deve incluir o link de pagamento {payment_link}