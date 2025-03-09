<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/InterestSetting.php';

// Initialize interest settings model
$interestSettingModel = new InterestSetting();

// Get current settings
$settings = $interestSettingModel->getSettings();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'interest_type' => $_POST['interest_type'] ?? 'percentage',
            'interest_value' => $_POST['interest_value'] ?? 0,
            'interest_enabled' => isset($_POST['interest_enabled']) ? 1 : 0,
            'penalty_type' => $_POST['penalty_type'] ?? 'percentage',
            'penalty_value' => $_POST['penalty_value'] ?? 0,
            'penalty_enabled' => isset($_POST['penalty_enabled']) ? 1 : 0
        ];
        
        $interestSettingModel->updateSettings($data);
        
        $_SESSION['flash_message'] = 'Configurações de juros atualizadas com sucesso!';
        $_SESSION['flash_type'] = 'success';
        header('Location: interest_settings.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Erro: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// Page title
$pageTitle = 'Configurações de Juros - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Configurações de Juros</h1>
            <p class="text-muted">Configure os juros e multas padrão do sistema</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h4>Configurações de Juros</h4>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="interest_enabled" id="interest_enabled" <?php echo ($settings && $settings['interest_enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="interest_enabled">
                                Ativar cobrança de juros
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="interest_type" class="form-label">Tipo de Juros</label>
                            <select class="form-select" id="interest_type" name="interest_type">
                                <option value="percentage" <?php echo ($settings && $settings['interest_type'] === 'percentage') ? 'selected' : ''; ?>>Porcentagem ao dia</option>
                                <option value="daily_value" <?php echo ($settings && $settings['interest_type'] === 'daily_value') ? 'selected' : ''; ?>>Valor fixo diário</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="interest_value" class="form-label">Valor dos Juros</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="interest_value" name="interest_value" step="0.01" min="0" value="<?php echo $settings ? $settings['interest_value'] : '0.00'; ?>">
                                <span class="input-group-text" id="interest_unit">%</span>
                            </div>
                            <small class="form-text text-muted">Para juros percentuais, informe o valor em % ao dia. Para valor fixo, informe o valor em R$ por dia de atraso.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>Configurações de Multa</h4>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="penalty_enabled" id="penalty_enabled" <?php echo ($settings && $settings['penalty_enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="penalty_enabled">
                                Ativar cobrança de multa
                            </label>
                        </div>

                    <div class="mb-3">
                        <label for="penalty_type" class="form-label">Tipo de Multa</label>
                            <select class="form-select" id="penalty_type" name="penalty_type">
                            <option value="percentage"  <?= ($settings['penalty_type'] ?? null) === 'percentage' ? 'selected' : '' ?>>Porcentagem</option>
                            <option value="fixed_value" <?= ($settings['penalty_type'] ?? null) === 'fixed_value' ? 'selected' : '' ?>>Valor fixo</option>
                        </select>
                </div>
                        
                        <div class="mb-3">
                            <label for="penalty_value" class="form-label">Valor da Multa</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="penalty_value" name="penalty_value" step="0.01" min="0" value="<?php echo $settings ? $settings['penalty_value'] : '0.00'; ?>">
                                <span class="input-group-text" id="penalty_unit">%</span>
                            </div>
                            <small class="form-text text-muted">A multa é aplicada uma única vez sobre o valor total em caso de atraso.</small>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Update interest unit based on selected type
    document.getElementById('interest_type').addEventListener('change', function() {
        const unit = this.value === 'percentage' ? '%' : 'R$';
        document.getElementById('interest_unit').textContent = unit;
    });
    
    // Update penalty unit based on selected type
    document.getElementById('penalty_type').addEventListener('change', function() {
        const unit = this.value === 'percentage' ? '%' : 'R$';
        document.getElementById('penalty_unit').textContent = unit;
    });
    
    // Trigger change events on page load to set correct units
    document.addEventListener('DOMContentLoaded', function() {
        const interestEvent = new Event('change');
        document.getElementById('interest_type').dispatchEvent(interestEvent);
        
        const penaltyEvent = new Event('change');
        document.getElementById('penalty_type').dispatchEvent(penaltyEvent);
    });
</script>

<?php include 'templates/footer.php'; ?>