<?php
global $pdo, $current_user_id;

// Get current date for comparison
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('-7 days'));

// Get task statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN due_date < ? AND status != 'concluida' THEN 1 ELSE 0 END) as overdue_tasks,
    SUM(CASE WHEN due_date = ? AND status != 'concluida' THEN 1 ELSE 0 END) as due_today_tasks
    FROM tasks WHERE user_id = ?");
$stmt->execute([$today, $today, $current_user_id]);
$task_stats = $stmt->fetch();

// Get contact count
$stmt = $pdo->prepare("SELECT COUNT(*) as total_contacts FROM contacts WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$contact_count = $stmt->fetchColumn();

// Get upcoming events (next 7 days)
$stmt = $pdo->prepare("SELECT COUNT(*) as upcoming_events FROM calendar_events 
                      WHERE user_id = ? AND event_date BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY)");
$stmt->execute([$current_user_id, $today, $today]);
$upcoming_events = $stmt->fetchColumn();

// Get recent tasks (last 7 days activity)
$stmt = $pdo->prepare("SELECT t.*, c.name as contact_name FROM tasks t 
                      LEFT JOIN contacts c ON t.contact_id = c.id 
                      WHERE t.user_id = ? 
                      ORDER BY t.updated_at DESC LIMIT 5");
$stmt->execute([$current_user_id]);
$recent_tasks = $stmt->fetchAll();

// Get overdue tasks
$stmt = $pdo->prepare("SELECT t.*, c.name as contact_name FROM tasks t 
                      LEFT JOIN contacts c ON t.contact_id = c.id 
                      WHERE t.user_id = ? AND t.due_date < ? AND t.status != 'concluida'
                      ORDER BY t.due_date ASC LIMIT 5");
$stmt->execute([$current_user_id, $today]);
$overdue_tasks = $stmt->fetchAll();

// Get today's events
$stmt = $pdo->prepare("SELECT e.*, c.name as contact_name FROM calendar_events e 
                      LEFT JOIN contacts c ON e.contact_id = c.id 
                      WHERE e.user_id = ? AND e.event_date = ?
                      ORDER BY e.event_time ASC");
$stmt->execute([$current_user_id, $today]);
$today_events = $stmt->fetchAll();

// Calculate productivity percentage
$total_tasks = $task_stats['total_tasks'] ?: 1;
$completed_percentage = round(($task_stats['completed_tasks'] / $total_tasks) * 100);
$circumference = 2 * M_PI * 50; // r=50
$progress_length = ($completed_percentage / 100) * $circumference;
$dash_offset = $circumference - $progress_length;
?>

<link href="/assets/css/dashboard.css" rel="stylesheet">

<div class="welcome-section mb-4">
    <div class="row align-items-center g-3">
        <div class="col-md-8">
            <h1 class="mb-2">Bem-vinda, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>! 👋</h1>
            <p class="mb-3 opacity-90">
                <?php
                // Fix deprecated strftime function
                $weekdays = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
                $months = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                $date = new DateTime($today);
                $weekday = $weekdays[$date->format('w')];
                $day = $date->format('d');
                $month = $months[(int)$date->format('n')];
                $year = $date->format('Y');
                echo "Hoje é {$weekday}, {$day} de {$month} de {$year}.";
                ?>
                Você tem <?php echo $task_stats['pending_tasks'] + $task_stats['in_progress_tasks']; ?> tarefas ativas
                <?php if ($task_stats['overdue_tasks'] > 0): ?>
                    e <strong><?php echo $task_stats['overdue_tasks']; ?> em atraso</strong>
                <?php endif; ?>.
            </p>
            <div class="quick-actions d-flex flex-wrap gap-2">
                <a href="/tasks" class="quick-action-btn">
                    <i class="fas fa-plus me-2"></i>Nova Tarefa
                </a>
                <a href="/calendar" class="quick-action-btn">
                    <i class="fas fa-calendar me-2"></i>Ver Agenda
                </a>
                <a href="/contacts" class="quick-action-btn">
                    <i class="fas fa-user-plus me-2"></i>Novo Contato
                </a>
            </div>
        </div>
      
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <a href="/tasks" class="text-decoration-none">
            <div class="card dashboard-card stat-card clickable-card">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="stat-number"><?php echo $task_stats['total_tasks']; ?></p>
                        <p class="stat-label">Total de Tarefas</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <a href="/tasks" class="text-decoration-none">
            <div class="card dashboard-card clickable-card" style="background: linear-gradient(135deg, #ffc107, #ffb300); color: white;">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="stat-number"><?php echo $task_stats['pending_tasks']; ?></p>
                        <p class="stat-label">Pendentes</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <a href="/tasks" class="text-decoration-none">
            <div class="card dashboard-card clickable-card" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="stat-number"><?php echo $task_stats['overdue_tasks']; ?></p>
                        <p class="stat-label">Em Atraso</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <a href="/contacts" class="text-decoration-none">
            <div class="card dashboard-card clickable-card" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="stat-number"><?php echo $contact_count; ?></p>
                        <p class="stat-label">Contatos</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">

<div class="row g-4">
    <div class="col-lg-4 col-md-6">
        <div class="card dashboard-card h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title d-flex align-items-center">
                    <i class="fas fa-calendar-day me-2 text-primary"></i>
                    Eventos de Hoje
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($today_events)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-calendar-check fa-2x mb-2 opacity-50"></i>
                        <p class="mb-0">Nenhum evento hoje</p>
                        <small>Aproveite o dia livre!</small>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($today_events as $event): ?>
                            <a href="/calendar" class="text-decoration-none">
                                <div class="event-item clickable-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1 me-2">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                            <?php if ($event['contact_name']): ?>
                                                <small class="opacity-90">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($event['contact_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($event['event_time']): ?>
                                            <span class="badge bg-light text-dark flex-shrink-0">
                                                <?php echo date('H:i', strtotime($event['event_time'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card dashboard-card h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title d-flex align-items-center">
                    <i class="fas fa-history me-2 text-info"></i>
                    Atividade Recente
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_tasks)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-clipboard-list fa-2x mb-2 opacity-50"></i>
                        <p class="mb-0">Nenhuma atividade recente</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($recent_tasks as $task): ?>
                            <a href="/tasks" class="text-decoration-none">
                                <div class="task-item clickable-item">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-1 flex-grow-1 me-2"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        <span class="priority-badge priority-<?php echo $task['priority']; ?> flex-shrink-0">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                        <small class="text-muted">
                                            Status: <span class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
                                        </small>
                                        <?php if ($task['due_date']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('d/m', strtotime($task['due_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/tasks" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Ver Todas as Tarefas
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12">
        <div class="card dashboard-card h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2 text-danger"></i>
                    Tarefas em Atraso
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($overdue_tasks)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-check-circle fa-2x mb-2 opacity-50"></i>
                        <p class="mb-0">Nenhuma tarefa em atraso</p>
                        <small>Parabéns! Você está em dia!</small>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($overdue_tasks as $task): ?>
                            <a href="/tasks" class="text-decoration-none">
                                <div class="task-item overdue clickable-item">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-1 flex-grow-1 me-2"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        <span class="priority-badge priority-<?php echo $task['priority']; ?> flex-shrink-0">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                        <small class="text-danger fw-bold">
                                            Venceu em <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                                        </small>
                                        <?php if ($task['contact_name']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($task['contact_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/tasks" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-eye me-1"></i>Ver Todas as Tarefas
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>