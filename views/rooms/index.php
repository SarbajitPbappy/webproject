<?php
/**
 * HostelEase — Rooms List View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Rooms</h2>
            <p class="text-muted mb-0">Manage hostel rooms and track occupancy</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=rooms/create" class="btn btn-primary-gradient">
            <i class="bi bi-plus-circle me-2"></i>Add New Room
        </a>
    </div>
</div>

<!-- Occupancy Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-glass stat-card">
            <div class="card-body">
                <div class="stat-icon bg-primary-subtle"><i class="bi bi-door-open text-primary"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Rooms</span>
                    <span class="stat-value"><?php echo count($rooms); ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-glass stat-card">
            <div class="card-body">
                <div class="stat-icon bg-success-subtle"><i class="bi bi-check-circle text-success"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Capacity</span>
                    <span class="stat-value"><?php echo $stats['total_capacity']; ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-glass stat-card">
            <div class="card-body">
                <div class="stat-icon bg-info-subtle"><i class="bi bi-people text-info"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Occupied</span>
                    <span class="stat-value"><?php echo $stats['total_occupied']; ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-glass stat-card">
            <div class="card-body">
                <div class="stat-icon bg-warning-subtle"><i class="bi bi-bar-chart text-warning"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Occupancy Rate</span>
                    <span class="stat-value"><?php echo $stats['occupancy_percent']; ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-glass mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?php echo BASE_URL; ?>" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="rooms/index">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All</option>
                    <option value="available" <?php echo ($_GET['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="full" <?php echo ($_GET['status'] ?? '') === 'full' ? 'selected' : ''; ?>>Full</option>
                    <option value="maintenance" <?php echo ($_GET['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select class="form-select" name="type">
                    <option value="">All Types</option>
                    <option value="single" <?php echo ($_GET['type'] ?? '') === 'single' ? 'selected' : ''; ?>>Single</option>
                    <option value="double" <?php echo ($_GET['type'] ?? '') === 'double' ? 'selected' : ''; ?>>Double</option>
                    <option value="triple" <?php echo ($_GET['type'] ?? '') === 'triple' ? 'selected' : ''; ?>>Triple</option>
                    <option value="dormitory" <?php echo ($_GET['type'] ?? '') === 'dormitory' ? 'selected' : ''; ?>>Dormitory</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Floor</label>
                <select class="form-select" name="floor">
                    <option value="">All</option>
                    <?php foreach ($floors as $f): ?>
                    <option value="<?php echo $f; ?>" <?php echo ($_GET['floor'] ?? '') == $f ? 'selected' : ''; ?>>Floor <?php echo $f; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-md-2">
                <a href="<?php echo BASE_URL; ?>?url=rooms/index" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Rooms Table -->
<div class="card card-glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="roomsTable">
                <thead>
                    <tr>
                        <th>Room #</th>
                        <th>Floor</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Occupancy</th>
                        <th>Status</th>
                        <th>Facilities</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms ?? [] as $room): ?>
                    <tr>
                        <td><strong><?php echo e($room['room_number']); ?></strong></td>
                        <td><?php echo $room['floor'] !== null ? 'Floor ' . e($room['floor']) : '—'; ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?php echo ucfirst(e($room['type'])); ?></span></td>
                        <td><?php echo e($room['capacity']); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                    <?php $pct = $room['capacity'] > 0 ? ($room['current_occupancy'] / $room['capacity']) * 100 : 0; ?>
                                    <div class="progress-bar bg-<?php echo $pct >= 100 ? 'danger' : ($pct >= 50 ? 'warning' : 'success'); ?>"
                                         style="width: <?php echo $pct; ?>%"></div>
                                </div>
                                <small class="text-nowrap"><?php echo $room['current_occupancy']; ?>/<?php echo $room['capacity']; ?></small>
                            </div>
                        </td>
                        <td>
                            <?php
                            $statusClass = match($room['status']) {
                                'available'   => 'success',
                                'full'        => 'danger',
                                'maintenance' => 'warning',
                                default       => 'secondary',
                            };
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?>">
                                <?php echo ucfirst(e($room['status'])); ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?php echo e(mb_strimwidth($room['facilities'] ?? '—', 0, 30, '...')); ?></small></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>?url=rooms/edit/<?php echo $room['id']; ?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="<?php echo BASE_URL; ?>?url=allocations/allocate?room_id=<?php echo $room['id']; ?>" class="btn btn-outline-info" title="Allocate"><i class="bi bi-person-plus"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $("#roomsTable").DataTable({ pageLength: 15, order: [[0, "asc"]] });
    }
});
</script>';
?>
