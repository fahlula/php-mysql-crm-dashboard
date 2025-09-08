<?php
global $pdo, $current_user_id;

// Get all tasks for current user grouped by status
$stmt = $pdo->prepare("SELECT t.*, c.name as contact_name FROM tasks t 
                      LEFT JOIN contacts c ON t.contact_id = c.id 
                      WHERE t.user_id = ? 
                      ORDER BY t.created_at DESC");
$stmt->execute([$current_user_id]);
$all_tasks = $stmt->fetchAll();

// Group tasks by status
$tasks_by_status = [
    'pendente' => [],
    'em_andamento' => [],
    'concluida' => []
];

foreach ($all_tasks as $task) {
    $tasks_by_status[$task['status']][] = $task;
}

// Get contacts for dropdown
$stmt = $pdo->prepare("SELECT id, name FROM contacts WHERE user_id = ? ORDER BY name");
$stmt->execute([$current_user_id]);
$contacts = $stmt->fetchAll();
?>

<link href="/assets/css/tasks.css" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">Tarefas</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                    <i class="fas fa-plus me-2"></i>Adicionar Tarefa
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="kanban-container">
        <!-- Pendentes Column -->
        <div class="kanban-column">
            <div class="kanban-header pendente">
                <div class="d-flex align-items-center">
                    <i class="fas fa-clock me-2"></i>
                    <span class="fw-bold">Pendente</span>
                </div>
                <span class="status-count"><?php echo count($tasks_by_status['pendente']); ?></span>
            </div>
            <div class="kanban-body">
                <?php if (empty($tasks_by_status['pendente'])): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p class="mb-0">Nenhuma tarefa pendente</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks_by_status['pendente'] as $task): ?>
                        <div class="task-card priority-<?php echo $task['priority']; ?>" 
                             onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                            <div class="task-content">
                                <div class="task-header">
                                    <h6 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($task['description']): ?>
                                    <p class="task-description">
                                        <?php echo htmlspecialchars(substr($task['description'], 0, 120)); ?>
                                        <?php if (strlen($task['description']) > 120): ?>...<?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <?php if ($task['due_date']): ?>
                                        <span class="task-meta-item <?php echo strtotime($task['due_date']) < time() ? 'overdue' : ''; ?>">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($task['contact_name']): ?>
                                        <span class="task-meta-item">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($task['contact_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="task-actions" onclick="event.stopPropagation();">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_task_status">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="status" value="em_andamento">
                                        <button type="submit" class="task-action-btn primary">
                                            <i class="fas fa-play"></i> Iniciar
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza?')">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="task-action-btn danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Em Andamento Column -->
        <div class="kanban-column">
            <div class="kanban-header em_andamento">
                <div class="d-flex align-items-center">
                    <i class="fas fa-spinner me-2"></i>
                    <span class="fw-bold">Em Andamento</span>
                </div>
                <span class="status-count"><?php echo count($tasks_by_status['em_andamento']); ?></span>
            </div>
            <div class="kanban-body">
                <?php if (empty($tasks_by_status['em_andamento'])): ?>
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <p class="mb-0">Nenhuma tarefa em andamento</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks_by_status['em_andamento'] as $task): ?>
                        <div class="task-card priority-<?php echo $task['priority']; ?>" 
                             onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                            <div class="task-content">
                                <div class="task-header">
                                    <h6 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($task['description']): ?>
                                    <p class="task-description">
                                        <?php echo htmlspecialchars(substr($task['description'], 0, 120)); ?>
                                        <?php if (strlen($task['description']) > 120): ?>...<?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <?php if ($task['due_date']): ?>
                                        <span class="task-meta-item <?php echo strtotime($task['due_date']) < time() ? 'overdue' : ''; ?>">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($task['contact_name']): ?>
                                        <span class="task-meta-item">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($task['contact_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="task-actions" onclick="event.stopPropagation();">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_task_status">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="status" value="concluida">
                                        <button type="submit" class="task-action-btn success">
                                            <i class="fas fa-check"></i> Concluir
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_task_status">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="status" value="pendente">
                                        <button type="submit" class="task-action-btn">
                                            <i class="fas fa-pause"></i> Pausar
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza?')">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="task-action-btn danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Concluídas Column -->
        <div class="kanban-column">
            <div class="kanban-header concluida">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <span class="fw-bold">Concluída</span>
                </div>
                <span class="status-count"><?php echo count($tasks_by_status['concluida']); ?></span>
            </div>
            <div class="kanban-body">
                <?php if (empty($tasks_by_status['concluida'])): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p class="mb-0">Nenhuma tarefa concluída</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks_by_status['concluida'] as $task): ?>
                        <div class="task-card priority-<?php echo $task['priority']; ?> completed" 
                             onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                            <div class="task-content">
                                <div class="task-header">
                                    <h6 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($task['description']): ?>
                                    <p class="task-description">
                                        <?php echo htmlspecialchars(substr($task['description'], 0, 120)); ?>
                                        <?php if (strlen($task['description']) > 120): ?>...<?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <?php if ($task['due_date']): ?>
                                        <span class="task-meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($task['contact_name']): ?>
                                        <span class="task-meta-item">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($task['contact_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="task-actions" onclick="event.stopPropagation();">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_task_status">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="status" value="pendente">
                                        <button type="submit" class="task-action-btn">
                                            <i class="fas fa-undo"></i> Reabrir
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza?')">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="task-action-btn danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Nova Tarefa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addTaskForm">
                    <input type="hidden" name="action" value="add_task">
                    <div class="mb-3">
                        <label for="taskTitle" class="form-label">Título da Tarefa *</label>
                        <input type="text" class="form-control" name="title" id="taskTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" id="taskDescription" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="taskStatus" class="form-label">Status</label>
                                <select class="form-control" name="status" id="taskStatus">
                                    <option value="pendente">Pendente</option>
                                    <option value="em_andamento">Em Andamento</option>
                                    <option value="concluida">Concluída</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="taskPriority" class="form-label">Prioridade</label>
                                <select class="form-control" name="priority" id="taskPriority">
                                    <option value="baixa">Baixa</option>
                                    <option value="media" selected>Média</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="taskDueDate" class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="due_date" id="taskDueDate">
                    </div>
                    <div class="mb-3">
                        <label for="taskContact" class="form-label">Contato Relacionado</label>
                        <select class="form-control" name="contact_id" id="taskContact">
                            <option value="">Selecione um contato...</option>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?php echo $contact['id']; ?>"><?php echo htmlspecialchars($contact['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="addTaskForm" class="btn btn-primary">Salvar Tarefa</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Tarefa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editTaskForm">
                    <input type="hidden" name="action" value="edit_task">
                    <input type="hidden" name="id" id="editTaskId">
                    <div class="mb-3">
                        <label for="editTaskTitle" class="form-label">Título da Tarefa *</label>
                        <input type="text" class="form-control" name="title" id="editTaskTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTaskDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" id="editTaskDescription" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editTaskStatus" class="form-label">Status</label>
                                <select class="form-control" name="status" id="editTaskStatus">
                                    <option value="pendente">Pendente</option>
                                    <option value="em_andamento">Em Andamento</option>
                                    <option value="concluida">Concluída</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editTaskPriority" class="form-label">Prioridade</label>
                                <select class="form-control" name="priority" id="editTaskPriority">
                                    <option value="baixa">Baixa</option>
                                    <option value="media">Média</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editTaskDueDate" class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="due_date" id="editTaskDueDate">
                    </div>
                    <div class="mb-3">
                        <label for="editTaskContact" class="form-label">Contato Relacionado</label>
                        <select class="form-control" name="contact_id" id="editTaskContact">
                            <option value="">Selecione um contato...</option>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?php echo $contact['id']; ?>"><?php echo htmlspecialchars($contact['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editTaskForm" class="btn btn-primary">Atualizar Tarefa</button>
            </div>
        </div>
    </div>
</div>

<script>
function editTask(task) {
    document.getElementById('editTaskId').value = task.id;
    document.getElementById('editTaskTitle').value = task.title;
    document.getElementById('editTaskDescription').value = task.description;
    document.getElementById('editTaskStatus').value = task.status;
    document.getElementById('editTaskPriority').value = task.priority;
    document.getElementById('editTaskDueDate').value = task.due_date;
    document.getElementById('editTaskContact').value = task.contact_id || '';
    
    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
}
</script>