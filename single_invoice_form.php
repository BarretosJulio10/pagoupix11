<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Client.php';
require_once 'includes/SingleInvoice.php';

// Initialize models
$clientModel = new Client();
$singleInvoiceModel = new SingleInvoice();

// Get all clients for select box
$clients = $clientModel->getAll();

// Check if editing an existing invoice
$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$invoice = [];
$isEdit = false;

if ($invoiceId > 0) {
    $invoice = $singleInvoiceModel->getById($invoiceId);
    $isEdit = true;
    
    if (!$invoice) {
        header('Location: single_invoices.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'client_id' => $_POST['client_id'] ?? '',
            'amount' => $_POST['amount'] ?? '',
            'due_date' => $_POST['due_date'] ?? ''
        ];
        
        if ($isEdit) {
            $singleInvoiceModel->update($invoiceId, $data);
            $_SESSION['flash_message'] = 'Fatura atualizada com sucesso!';
        } else {
            $singleInvoiceModel->create($data);
            $_SESSION['flash_message'] = 'Fatura criada com sucesso!';
        }
        
        $_SESSION['flash_type'] = 'success';
        header('Location: single_invoices.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Erro: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// Page title
$pageTitle = ($isEdit ? 'Editar' : 'Nova') . ' Fatura Avulsa - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><?php echo $isEdit ? 'Editar' : 'Nova'; ?> Fatura Avulsa</h1>
            <p class="text-muted">Preencha os dados da fatura</p>
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
                            <option value="<?php echo $client['id']; ?>" <?php echo ($invoice['client_id'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo $client['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="amount" class="form-label">Valor *</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" value="<?php echo $invoice['amount'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="due_date" class="form-label">Data de Vencimento *</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $invoice['due_date'] ?? ''; ?>" required>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="single_invoices.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>