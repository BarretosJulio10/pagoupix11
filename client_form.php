<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Client.php';

// Initialize client model
$clientModel = new Client();

// Check if editing an existing client
$clientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$client = [];
$isEdit = false;

if ($clientId > 0) {
    $client = $clientModel->getById($clientId);
    $isEdit = true;
    
    if (!$client) {
        // Redirect to clients list if client not found
        header('Location: clients.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? null,
            'document' => $_POST['document'] ?? null
        ];
        
        if ($isEdit) {
            // Update existing client
            $clientModel->update($clientId, $data);
            $_SESSION['flash_message'] = 'Cliente atualizado com sucesso!';
            
            // Redirect to clients list for updates
            header('Location: clients.php');
            exit;
        } else {
            // Create new client
            $newClientId = $clientModel->create($data);
            $_SESSION['flash_message'] = 'Cliente criado com sucesso!';
            $_SESSION['flash_type'] = 'success';
            
            // Redirect to subscription form with client_id
            header('Location: subscription_form.php?client_id=' . $newClientId);
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Erro: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// Page title
$pageTitle = ($isEdit ? 'Editar' : 'Novo') . ' Cliente - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><?php echo $isEdit ? 'Editar' : 'Novo'; ?> Cliente</h1>
            <p class="text-muted">Preencha os dados do cliente</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="name" class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $client['name'] ?? ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $client['email'] ?? ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $client['phone'] ?? ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="document" class="form-label">Documento (CPF/CNPJ)</label>
                    <input type="text" class="form-control" id="document" name="document" value="<?php echo $client['document'] ?? ''; ?>">
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="clients.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>