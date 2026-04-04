<?php /** Room roster — who is in which room */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Room roster</h2>
            <p class="text-muted mb-0">Live view of every room and current occupants (active allocations).</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=allocations/allocate" class="btn btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i>Allocations
        </a>
    </div>
</div>

<div class="card card-glass">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="occupancyTable">
                <thead class="table-light">
                    <tr>
                        <th>Room</th>
                        <th>Floor</th>
                        <th>Type</th>
                        <th>Occupancy</th>
                        <th>Status</th>
                        <th>Residents</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($board as $row): ?>
                    <tr>
                        <td><strong><?php echo e($row['room_number']); ?></strong></td>
                        <td><?php echo e((string) ($row['floor'] ?? '—')); ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?php echo ucfirst(e($row['type'])); ?></span></td>
                        <td>
                            <?php echo (int) $row['current_occupancy']; ?> / <?php echo (int) $row['capacity']; ?>
                            <?php
                            $pct = (int) $row['capacity'] > 0
                                ? round(((int) $row['current_occupancy'] / (int) $row['capacity']) * 100)
                                : 0;
                            ?>
                            <div class="progress mt-1" style="height:4px;max-width:120px">
                                <div class="progress-bar" style="width: <?php echo min(100, $pct); ?>%"></div>
                            </div>
                        </td>
                        <td><span class="badge bg-<?php echo $row['status'] === 'full' ? 'warning' : ($row['status'] === 'maintenance' ? 'danger' : 'success'); ?>-subtle text-<?php echo $row['status'] === 'full' ? 'warning' : ($row['status'] === 'maintenance' ? 'danger' : 'success'); ?>"><?php echo ucfirst(e($row['status'])); ?></span></td>
                        <td>
                            <?php if (!empty($row['occupant_labels'])): ?>
                            <small><?php echo e($row['occupant_labels']); ?></small>
                            <?php else: ?>
                            <span class="text-muted">— Vacant —</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
