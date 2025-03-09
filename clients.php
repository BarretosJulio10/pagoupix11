<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Client.php';

// Initialize client model
$clientModel = new Client();

// Get all clients
$clients = $clientModel->getAll();

// Page title
$pageTitle = 'Clientes - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Clientes</h1>
            <p class="text-muted">Gerenciamento de clientes</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="client_form.php" class="btn btn-primary" data-bs-toggle="tooltip" title="Adicionar novo cliente">
                <i class="fas fa-user-plus"></i> Novo Cliente
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (count($clients) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="clientsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Documento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?php echo $client['id']; ?></td>
                                    <td data-field="name">
                                        <a href="client_view.php?id=<?php echo $client['id']; ?>" data-bs-toggle="tooltip" title="Ver detalhes e histórico de pagamentos">
                                            <?php echo $client['name']; ?>
                                        </a>
                                    </td>
                                    <td data-field="email"><?php echo $client['email']; ?></td>
                                    <td data-field="phone"><?php echo $client['phone'] ?? '-'; ?></td>
                                    <td data-field="document"><?php echo $client['document'] ?? '-'; ?></td>
                                    <td>
                                        <a href="client_view.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalhes e histórico de pagamentos">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="client_form.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar informações do cliente">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger delete-client" data-client-id="<?php echo $client['id']; ?>" data-bs-toggle="tooltip" title="Excluir cliente">
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
                    Nenhum cliente cadastrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteClientModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita e excluirá todas as mensalidades associadas.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Excluir</button>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteClientModal'));
    let clientIdToDelete = null;

    // Delete client functionality
    document.querySelectorAll('.delete-client').forEach(button => {
        button.addEventListener('click', function() {
            clientIdToDelete = this.getAttribute('data-client-id');
            deleteModal.show();
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (!clientIdToDelete) return;

        const button = document.querySelector(`.delete-client[data-client-id="${clientIdToDelete}"]`);
        
        fetch('delete_client.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'client_id=' + clientIdToDelete
        })
        .then(response => response.json())
        .then(data => {
            deleteModal.hide();
            if (data.success) {
                const row = button.closest('tr');
                row.remove();
                // Check if table is empty
                const tbody = document.querySelector('#clientsTable tbody');
                if (tbody.children.length === 0) {
                    location.reload(); // Reload to show empty state
                }
            } else {
                alert('Erro ao excluir cliente: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            deleteModal.hide();
            console.error('Error:', error);
            alert('Erro ao excluir cliente. Por favor, tente novamente.');
        });
    });
});
</script>

<!-- Include filters script -->
<script src="assets/js/filters.js"></script>