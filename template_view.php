<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Communication.php';

// Initialize communication model
$communicationModel = new Communication();

// Check if template ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID do template não fornecido';
    $_SESSION['flash_type'] = 'danger';
    header('Location: templates.php');
    exit;
}

// Get template ID
$templateId = (int)$_GET['id'];

// Get template details
$template = $communicationModel->getTemplateById($templateId);

if (!$template) {
    $_SESSION['flash_message'] = 'Template não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: templates.php');
    exit;
}

// Map template type to readable label
$typeLabels = [
    'payment_reminder' => 'Lembrete de Pagamento',
    'payment_confirmation' => 'Confirmação de Pagamento',
    'payment_overdue' => 'Pagamento em Atraso',
    'welcome' => 'Boas-vindas'
];

$typeLabel = $typeLabels[$template['type']] ?? $template['type'];

// Page title
$pageTitle = 'Visualizar Template - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Visualizar Template</h1>
            <p class="text-muted">Detalhes do template de mensagem</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="templates.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <a href="template_form.php?id=<?php echo $template['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Informações do Template</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Nome:</div>
                <div class="col-md-9"><?php echo htmlspecialchars($template['name']); ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Tipo:</div>
                <div class="col-md-9"><?php echo htmlspecialchars($typeLabel); ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Assunto:</div>
                <div class="col-md-9"><?php echo htmlspecialchars($template['subject']); ?></div>
            </div>
            <?php if (!empty($template['image_path'])): ?>
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Imagem:</div>
                <div class="col-md-9">
                    <img src="<?php echo htmlspecialchars($template['image_path']); ?>" alt="Template Image" class="img-thumbnail" style="max-height: 200px">
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Corpo da Mensagem</h5>
        </div>
        <div class="card-body">
            <div class="border p-3 bg-light">
                <?php echo nl2br(htmlspecialchars($template['body'])); ?>
            </div>
            
            <div class="mt-3 small text-muted">
                <strong>Placeholders disponíveis:</strong><br>
                {client_name} - Nome do cliente<br>
                {amount} - Valor da mensalidade/pagamento<br>
                {due_date} - Data de vencimento<br>
                {payment_link} - Link de pagamento
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>