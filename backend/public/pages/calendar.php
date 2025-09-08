<?php
global $pdo, $current_user_id;

// Get current month and year from URL or use current date
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Calculate previous and next month/year
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get all events for current user
$stmt = $pdo->prepare("SELECT e.*, c.name as contact_name FROM calendar_events e 
                      LEFT JOIN contacts c ON e.contact_id = c.id 
                      WHERE e.user_id = ? 
                      ORDER BY e.event_date ASC, e.event_time ASC");
$stmt->execute([$current_user_id]);
$events = $stmt->fetchAll();

// Get contacts for dropdown
$stmt = $pdo->prepare("SELECT id, name FROM contacts WHERE user_id = ? ORDER BY name");
$stmt->execute([$current_user_id]);
$contacts = $stmt->fetchAll();

// Get current month events for calendar display
$current_month_str = sprintf('%04d-%02d', $year, $month);
$calendar_events = [];
foreach ($events as $event) {
    if (strpos($event['event_date'], $current_month_str) === 0) {
        $day = date('j', strtotime($event['event_date']));
        $calendar_events[$day][] = $event;
    }
}

// Generate calendar data
$first_day = sprintf('%04d-%02d-01', $year, $month);
$days_in_month = date('t', strtotime($first_day));
$first_weekday = date('w', strtotime($first_day));
$month_names = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>

<link href="/assets/css/calendar.css" rel="stylesheet">

<div class="container-fluid">
    <!-- Success Alert -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Calendar Header -->
    <div class="calendar-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-2">Calendário</h1>
                <p class="mb-0 opacity-90"><?php echo $month_names[$month] . ' de ' . $year; ?></p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="btn-group me-2" role="group">
                    <a href="?page=calendar&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                       class="btn btn-outline-light">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a href="?page=calendar&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                       class="btn btn-outline-light">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-plus me-2"></i>Adicionar Evento
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th class="text-center py-3">Domingo</th>
                            <th class="text-center py-3">Segunda</th>
                            <th class="text-center py-3">Terça</th>
                            <th class="text-center py-3">Quarta</th>
                            <th class="text-center py-3">Quinta</th>
                            <th class="text-center py-3">Sexta</th>
                            <th class="text-center py-3">Sábado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $day = 1;
                        $today = date('Y-m-d');
                        
                        for ($week = 0; $week < 6; $week++):
                            if ($day > $days_in_month && $week > 0) break;
                        ?>
                        <tr>
                            <?php for ($weekday = 0; $weekday < 7; $weekday++): ?>
                            <td class="calendar-cell p-2" style="height: 120px; vertical-align: top; width: 14.28%;">
                                <?php
                                if ($week == 0 && $weekday < $first_weekday) {
                                    // Empty cell for days before month starts
                                    echo '<div class="calendar-day-empty"></div>';
                                } elseif ($day <= $days_in_month) {
                                    $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    $is_today = ($current_date === $today);
                                    $day_class = $is_today ? 'calendar-day-today' : 'calendar-day-number';
                                    
                                    echo '<div class="' . $day_class . ' mb-2">' . $day . '</div>';
                                    
                                    // Display events for this day
                                    if (isset($calendar_events[$day])) {
                                        echo '<div class="calendar-events">';
                                        foreach ($calendar_events[$day] as $event) {
                                            $event_time = $event['event_time'] ? date('H:i', strtotime($event['event_time'])) : '';
                                            echo '<div class="event-badge mb-1" onclick="editEvent(' . htmlspecialchars(json_encode($event)) . ')">';
                                            echo '<div class="event-title">' . htmlspecialchars($event['title']) . '</div>';
                                            if ($event_time) {
                                                echo '<div class="event-time">' . $event_time . '</div>';
                                            }
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    }
                                    $day++;
                                } else {
                                    echo '<div class="calendar-day-empty"></div>';
                                }
                                ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Novo Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addEventForm">
                    <input type="hidden" name="action" value="add_event">
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">Título do Evento *</label>
                        <input type="text" class="form-control" name="title" id="eventTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventDate" class="form-label">Data *</label>
                        <input type="date" class="form-control" name="event_date" id="eventDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventTime" class="form-label">Horário</label>
                        <input type="time" class="form-control" name="event_time" id="eventTime">
                    </div>
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" id="eventDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="eventContact" class="form-label">Contato Relacionado</label>
                        <select class="form-control" name="contact_id" id="eventContact">
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
                <button type="submit" form="addEventForm" class="btn btn-primary">Salvar Evento</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editEventForm">
                    <input type="hidden" name="action" value="edit_event">
                    <input type="hidden" name="id" id="editEventId">
                    <div class="mb-3">
                        <label for="editEventTitle" class="form-label">Título do Evento *</label>
                        <input type="text" class="form-control" name="title" id="editEventTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEventDate" class="form-label">Data *</label>
                        <input type="date" class="form-control" name="event_date" id="editEventDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEventTime" class="form-label">Horário</label>
                        <input type="time" class="form-control" name="event_time" id="editEventTime">
                    </div>
                    <div class="mb-3">
                        <label for="editEventDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" id="editEventDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editEventContact" class="form-label">Contato Relacionado</label>
                        <select class="form-control" name="contact_id" id="editEventContact">
                            <option value="">Selecione um contato...</option>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?php echo $contact['id']; ?>"><?php echo htmlspecialchars($contact['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este evento?')">
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="id" id="deleteEventId">
                    <button type="submit" class="btn btn-danger me-auto">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editEventForm" class="btn btn-primary">Atualizar Evento</button>
            </div>
        </div>
    </div>
</div>

<script>
function editEvent(event) {
    document.getElementById('editEventId').value = event.id;
    document.getElementById('deleteEventId').value = event.id;
    document.getElementById('editEventTitle').value = event.title;
    document.getElementById('editEventDate').value = event.event_date;
    document.getElementById('editEventTime').value = event.event_time || '';
    document.getElementById('editEventDescription').value = event.description || '';
    document.getElementById('editEventContact').value = event.contact_id || '';
    
    new bootstrap.Modal(document.getElementById('editEventModal')).show();
}

function goToToday() {
    const today = new Date();
    const month = today.getMonth() + 1;
    const year = today.getFullYear();
    window.location.href = `/calendar?month=${month}&year=${year}`;
}

// Set default date for new events to today
document.addEventListener('DOMContentLoaded', function() {
    const eventDateInput = document.getElementById('eventDate');
    if (eventDateInput && !eventDateInput.value) {
        const today = new Date();
        const dateString = today.toISOString().split('T')[0];
        eventDateInput.value = dateString;
    }
});
</script>