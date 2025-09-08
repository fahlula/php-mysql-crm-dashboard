<?php
global $pdo, $current_user_id;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE user_id = ? AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ?) ORDER BY name");
    $searchTerm = '%' . $search . '%';
    $stmt->execute([$current_user_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE user_id = ? ORDER BY name");
    $stmt->execute([$current_user_id]);
}
$contacts = $stmt->fetchAll();
?>

<link href="/assets/css/contacts.css" rel="stylesheet">

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-2">Contatos</h1>
            <p class="mb-0 opacity-90">Gerencie seus contatos e mantenha suas informações organizadas</p>
        </div>
        <button class="btn btn-light"  data-bs-toggle="modal" data-bs-target="#addContactModal">
            <i class="fas fa-plus me-2"></i>Adicionar Contato
        </button>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <?php echo htmlspecialchars($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <?php echo htmlspecialchars($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-12">
        <form method="GET" action="/contacts" class="d-flex">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Buscar contatos por nome, email, telefone ou empresa..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search)): ?>
                    <a href="/contacts" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($search)): ?>
    <div class="alert alert-info">
        <i class="fas fa-search me-2"></i>
        Mostrando resultados para "<strong><?php echo htmlspecialchars($search); ?></strong>" 
        (<?php echo count($contacts); ?> contato<?php echo count($contacts) != 1 ? 's' : ''; ?> encontrado<?php echo count($contacts) != 1 ? 's' : ''; ?>)
    </div>
<?php endif; ?>

<div class="card">

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                    <tr class="contact-row">
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($contact['image'])): ?>
                                    <img src="<?= htmlspecialchars($contact['image']) ?>" 
                                         alt="<?= htmlspecialchars($contact['name']) ?>" 
                                         class="contact-image me-3">
                                <?php else: ?>
                                    <div class="contact-avatar me-3">
                                        <?= strtoupper(substr($contact['name'], 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($contact['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($contact['email']) ?></td>
                        <td><?= htmlspecialchars($contact['phone']) ?></td>
                        <td><?= htmlspecialchars($contact['company']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#viewContactModal<?= $contact['id'] ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary me-1" onclick="editContact(<?php echo htmlspecialchars(json_encode($contact)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="action" value="delete_contact">
                                <input type="hidden" name="id" value="<?= $contact['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addContactForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_contact">
                    <div class="mb-3">
                        <label for="contactImage" class="form-label">Foto do Contato</label>
                        <input type="file" class="form-control" name="contact_image" id="contactImage" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                        <div class="mt-2">
                            <img id="imagePreview" src="" alt="Preview" style="max-width: 100px; max-height: 100px; border-radius: 8px; display: none;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="phone">
                    </div>
                    <div class="mb-3">
                        <label for="company" class="form-label">Company</label>
                        <input type="text" class="form-control" name="company" id="company">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="addContactForm" class="btn btn-primary">Adicionar Contato</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Contato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editContactForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_contact">
                    <input type="hidden" name="id" id="editContactId">
                    <div class="mb-3">
                        <label for="editContactImage" class="form-label">Foto do Contato</label>
                        <input type="file" class="form-control" name="contact_image" id="editContactImage" accept="image/*" onchange="previewImage(this, 'editImagePreview')">
                        <div class="mt-2">
                            <img id="editImagePreview" src="" alt="Preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editContactName" class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="editContactName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editContactEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editContactEmail">
                    </div>
                    <div class="mb-3">
                        <label for="editContactPhone" class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="editContactPhone">
                    </div>
                    <div class="mb-3">
                        <label for="editContactCompany" class="form-label">Company</label>
                        <input type="text" class="form-control" name="company" id="editContactCompany">
                    </div>
                    <div class="mb-3">
                        <label for="editContactAddress" class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="editContactAddress" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editContactNotes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="editContactNotes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editContactForm" class="btn btn-primary">Atualizar Contato</button>
            </div>
        </div>
    </div>
</div>

<!-- View Contact Modals -->
<?php foreach ($contacts as $contact): ?>
<div class="modal fade" id="viewContactModal<?= $contact['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($contact['name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($contact['image'])): ?>
                    <div class="text-center mb-3">
                        <img src="<?= htmlspecialchars($contact['image']) ?>" 
                             alt="<?= htmlspecialchars($contact['name']) ?>" 
                             style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid var(--border-color);">
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-sm-3"><strong>Email:</strong></div>
                    <div class="col-sm-9"><?= htmlspecialchars($contact['email']) ?: '-' ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-3"><strong>Phone:</strong></div>
                    <div class="col-sm-9"><?= htmlspecialchars($contact['phone']) ?: '-' ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-3"><strong>Company:</strong></div>
                    <div class="col-sm-9"><?= htmlspecialchars($contact['company']) ?: '-' ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-3"><strong>Address:</strong></div>
                    <div class="col-sm-9"><?= htmlspecialchars($contact['address']) ?: '-' ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-3"><strong>Notes:</strong></div>
                    <div class="col-sm-9"><?= htmlspecialchars($contact['notes']) ?: '-' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function editContact(contact) {
    document.getElementById('editContactId').value = contact.id;
    document.getElementById('editContactName').value = contact.name;
    document.getElementById('editContactEmail').value = contact.email || '';
    document.getElementById('editContactPhone').value = contact.phone || '';
    document.getElementById('editContactCompany').value = contact.company || '';
    document.getElementById('editContactAddress').value = contact.address || '';
    document.getElementById('editContactNotes').value = contact.notes || '';
    
    // Handle image preview
    const editImagePreview = document.getElementById('editImagePreview');
    if (contact.image) {
        editImagePreview.src = contact.image;
        editImagePreview.style.display = 'block';
    } else {
        editImagePreview.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('editContactModal')).show();
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Check file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('A imagem deve ter no máximo 2MB');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        // Check file type
        if (!file.type.startsWith('image/')) {
            alert('Por favor, selecione apenas arquivos de imagem');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
}

// Real-time search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const contactRows = document.querySelectorAll('.contact-row');
    
    if (searchInput && contactRows.length > 0) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.toLowerCase().trim();
                
                contactRows.forEach(row => {
                    const searchableText = row.textContent.toLowerCase();
                    if (searchableText.includes(searchTerm) || searchTerm === '') {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Update visible count
                const visibleRows = document.querySelectorAll('.contact-row:not([style*="display: none"])');
                const countElement = document.querySelector('.search-results-count');
                if (countElement) {
                    countElement.textContent = `${visibleRows.length} contato${visibleRows.length !== 1 ? 's' : ''} encontrado${visibleRows.length !== 1 ? 's' : ''}`;
                }
            }, 300);
        });
    }
    
    // Handle drag and drop for image upload
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    
    imageInputs.forEach(input => {
        const container = input.closest('.mb-3');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            container.addEventListener(eventName, preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            container.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            container.addEventListener(eventName, unhighlight, false);
        });
        
        container.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                input.files = files;
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight(e) {
        e.currentTarget.classList.add('dragover');
    }
    
    function unhighlight(e) {
        e.currentTarget.classList.remove('dragover');
    }
});
</script>