<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force UTF-8 output to match DB/Server encoding
header('Content-Type: text/html; charset=UTF-8');

session_start();

// Include database connection
try {
    require_once __DIR__ . '/config/database.php';
} catch (Exception $e) {
    die("Database configuration error: " . $e->getMessage());
}

// Include image processing utilities
require_once 'includes/image_utils.php';

// Authentication check
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
}

// Get current user ID
$current_user_id = $_SESSION['user_id'] ?? null;

// Handle authentication and form submissions
if ($_POST) {
    switch ($_POST['action'] ?? '') {
        case 'login':
            $stmt = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            $user = $stmt->fetch();
            
            if ($user && md5($_POST['password']) === $user['password_hash']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: /dashboard');
                exit;
            } else {
                $_SESSION['error'] = 'Email ou senha inválidos';
                header('Location: /login');
                exit;
            }
            
        case 'register':
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Email já está em uso';
                header('Location: /register');
                exit;
            }
            
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['email'], $password_hash]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $_POST['name'];
            $_SESSION['success'] = 'Conta criada com sucesso!';
            header('Location: /dashboard');
            exit;
            
        case 'upload_profile_image':
            requireAuth();
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($_FILES['profile_image']['name']);
                $extension = strtolower($file_info['extension']);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($extension, $allowed_extensions)) {
                    // Read file and convert to base64
                    $image_data = file_get_contents($_FILES['profile_image']['tmp_name']);
                    $base64 = base64_encode($image_data);
                    $mime_type = $_FILES['profile_image']['type'];
                    
                    // Create data URL
                    $data_url = "data:$mime_type;base64,$base64";
                    
                    // Update user profile image
                    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$data_url, $current_user_id]);
                    
                    $_SESSION['success'] = 'Foto de perfil atualizada com sucesso!';
                } else {
                    $_SESSION['error'] = 'Formato de arquivo não permitido. Use JPG, PNG ou GIF.';
                }
            } else {
                $_SESSION['error'] = 'Erro ao fazer upload da imagem.';
            }
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
            
        case 'logout':
            session_destroy();
            header('Location: /login');
            exit;
            
        case 'add_contact':
            requireAuth();
            $image = null;
            if (isset($_FILES['contact_image'])) {
                $image = processContactImage($_FILES['contact_image']);
            }
            
            $stmt = $pdo->prepare("INSERT INTO contacts (user_id, name, email, phone, company, address, notes, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $current_user_id,
                $_POST['name'],
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['company'] ?: null,
                $_POST['address'] ?: null,
                $_POST['notes'] ?: null,
                $image
            ]);
            
            $_SESSION['success'] = 'Contato adicionado com sucesso!';
            header('Location: /contacts');
            exit;
            
        case 'edit_contact':
            requireAuth();
            $contactId = (int)$_POST['id'];
            
            // Get current contact data
            $stmt = $pdo->prepare("SELECT image FROM contacts WHERE id = ? AND user_id = ?");
            $stmt->execute([$contactId, $current_user_id]);
            $currentContact = $stmt->fetch();
            
            if (!$currentContact) {
                $_SESSION['error'] = 'Contato não encontrado.';
                header('Location: /contacts');
                exit;
            }
            
            $image = $currentContact['image']; // Keep existing image by default
            
            // Process new image if uploaded
            if (isset($_FILES['contact_image']) && $_FILES['contact_image']['error'] === UPLOAD_ERR_OK) {
                $image = processContactImage($_FILES['contact_image']);
            }
            
            $stmt = $pdo->prepare("UPDATE contacts SET name = ?, email = ?, phone = ?, company = ?, address = ?, notes = ?, image = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['company'] ?: null,
                $_POST['address'] ?: null,
                $_POST['notes'] ?: null,
                $image,
                $contactId,
                $current_user_id
            ]);
            
            $_SESSION['success'] = 'Contato atualizado com sucesso!';
            header('Location: /contacts');
            exit;
            
        case 'delete_contact':
            requireAuth();
            $contactId = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ? AND user_id = ?");
            $stmt->execute([$contactId, $current_user_id]);
            $_SESSION['success'] = 'Contato excluído com sucesso!';
            header('Location: /contacts');
            exit;
            
        case 'add_task':
            requireAuth();
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, status, priority, due_date, contact_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $current_user_id,
                $_POST['title'],
                $_POST['description'],
                $_POST['status'],
                $_POST['priority'],
                $_POST['due_date'] ?: null,
                $_POST['contact_id'] ?: null
            ]);
            $_SESSION['success'] = 'Tarefa adicionada com sucesso!';
            header('Location: /tasks');
            exit;
            
        case 'edit_task':
            requireAuth();
            $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, status=?, priority=?, due_date=?, contact_id=? WHERE id=? AND user_id=?");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['status'],
                $_POST['priority'],
                $_POST['due_date'] ?: null,
                $_POST['contact_id'] ?: null,
                $_POST['id'],
                $current_user_id
            ]);
            $_SESSION['success'] = 'Tarefa atualizada com sucesso!';
            header('Location: /tasks');
            exit;
            
        case 'update_task_status':
            requireAuth();
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
            $_SESSION['success'] = 'Status da tarefa atualizado!';
            header('Location: /tasks');
            exit;
            
        case 'delete_task':
            requireAuth();
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $current_user_id]);
            $_SESSION['success'] = 'Tarefa removida com sucesso!';
            header('Location: /tasks');
            exit;
            
        case 'add_event':
            requireAuth();
            $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, title, description, event_date, event_time, contact_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $current_user_id,
                $_POST['title'],
                $_POST['description'],
                $_POST['event_date'],
                $_POST['event_time'] ?: null,
                $_POST['contact_id'] ?: null
            ]);
            $_SESSION['success'] = 'Evento adicionado com sucesso!';
            header('Location: /calendar');
            exit;
            
        case 'edit_event':
            requireAuth();
            $stmt = $pdo->prepare("UPDATE calendar_events SET title=?, description=?, event_date=?, event_time=?, contact_id=? WHERE id=? AND user_id=?");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['event_date'],
                $_POST['event_time'] ?: null,
                $_POST['contact_id'] ?: null,
                $_POST['id'],
                $current_user_id
            ]);
            $_SESSION['success'] = 'Evento atualizado com sucesso!';
            header('Location: /calendar');
            exit;
            
        case 'delete_event':
            requireAuth();
            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $current_user_id]);
            $_SESSION['success'] = 'Evento removido com sucesso!';
            header('Location: /calendar');
            exit;
    }
}

// Contact Manager Application
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = rtrim($path, '/');
if (empty($path)) {
    $path = '/login';
}

// Basic routing for Contact Manager
function renderLayout($page) {
    global $current_user_id, $pdo;
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CRM Professional</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="/assets/css/global.css" rel="stylesheet">
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="/dashboard">
                    <i class="fas fa-briefcase me-2"></i>CRM Professional
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="/dashboard">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'contacts' ? 'active' : ''; ?>" href="/contacts">
                                <i class="fas fa-users me-1"></i>Contatos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'calendar' ? 'active' : ''; ?>" href="/calendar">
                                <i class="fas fa-calendar me-1"></i>Calendário
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'tasks' ? 'active' : ''; ?>" href="/tasks">
                                <i class="fas fa-tasks me-1"></i>Tarefas
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <?php
                                // Get user profile image
                                $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                                $stmt->execute([$current_user_id]);
                                $user_data = $stmt->fetch();
                                $profile_image = $user_data['profile_image'] ?? null;
                                ?>
                                <div class="profile-container position-relative me-2">
                                    <?php if ($profile_image): ?>
                                        <img src="<?php echo htmlspecialchars($profile_image); ?>" class="profile-image" alt="Profile">
                                    <?php else: ?>
                                        <div class="profile-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-user text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="upload-overlay" onclick="document.getElementById('profileImageInput').click()">
                                        <i class="fas fa-camera text-white"></i>
                                    </div>
                                </div>
                                <?php echo $_SESSION['user_name'] ?? 'Usuário'; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <form method="POST" enctype="multipart/form-data" id="profileForm" style="display: none;">
                                        <input type="hidden" name="action" value="upload_profile_image">
                                        <input type="file" id="profileImageInput" name="profile_image" accept="image/*" onchange="this.form.submit()">
                                    </form>
                                    <button class="dropdown-item" onclick="document.getElementById('profileImageInput').click()">
                                        <i class="fas fa-camera me-2"></i>Alterar Foto
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" class="dropdown-item-text">
                                        <input type="hidden" name="action" value="logout">
                                        <button type="submit" class="btn btn-link p-0 text-decoration-none text-dark">
                                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main content -->
        <div class="container-fluid main-content">
            <?php include "pages/{$page}.php"; ?>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

// Page routing
switch ($path) {
    case '/login':
        include 'pages/login.php';
        break;
    case '/register':
        include 'pages/register.php';
        break;
    case '/dashboard':
        requireAuth();
        renderLayout('dashboard');
        break;
    case '/contacts':
        requireAuth();
        renderLayout('contacts');
        break;
    case '/calendar':
        requireAuth();
        renderLayout('calendar');
        break;
    case '/tasks':
        requireAuth();
        renderLayout('tasks');
        break;
    default:
        if ($current_user_id) {
            header('Location: /dashboard');
        } else {
            header('Location: /login');
        }
        exit;
}
?>