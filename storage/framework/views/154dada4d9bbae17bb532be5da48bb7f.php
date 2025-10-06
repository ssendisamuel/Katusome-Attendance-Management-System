<?php $__env->startSection('title', 'Schedule Series'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Schedule Series</h4>
  <div>
    <a href="<?php echo e(route('admin.series.create')); ?>" class="btn btn-sm btn-primary px-2">Add Series</a>
    <button type="button" class="btn btn-sm btn-success ms-2 px-2" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">Generate All</button>
  </div>
</div>
<?php if(session('success')): ?>
  <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
  <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
<?php endif; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Course</th>
          <th>Group</th>
          <th>Lecturer</th>
          <th>Dates</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $series; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <tr>
            <td><?php echo e($s->name); ?></td>
            <td><?php echo e(optional($s->course)->name); ?></td>
            <td><?php echo e(optional($s->group)->name); ?></td>
            <td><?php echo e(optional($s->lecturer)->name); ?></td>
            <td><?php echo e($s->start_date); ?> → <?php echo e($s->end_date); ?></td>
            <td class="text-end">
              <a href="<?php echo e(route('admin.series.edit', $s)); ?>" class="btn btn-sm btn-outline-primary px-2">Edit</a>
              <button type="button" class="btn btn-sm btn-success ms-1 px-2 py-1" data-bs-toggle="modal" data-bs-target="#generateModal-<?php echo e($s->id); ?>">Generate</button>
              <form action="<?php echo e(route('admin.series.destroy', $s)); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger px-2" onclick="return confirm('Delete this series?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?php echo e($series->links()); ?></div>
</div>

<?php $__currentLoopData = $series; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <!-- Per-series Generate Modal -->
  <div class="modal fade" id="generateModal-<?php echo e($s->id); ?>" tabindex="-1" aria-labelledby="generateModalLabel-<?php echo e($s->id); ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="generateModalLabel-<?php echo e($s->id); ?>">Generate from "<?php echo e($s->name); ?>"</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="<?php echo e(route('admin.series.generate-schedules', $s)); ?>">
          <?php echo csrf_field(); ?>
          <div class="modal-body">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="overwrite-<?php echo e($s->id); ?>" name="overwrite">
              <label class="form-check-label" for="overwrite-<?php echo e($s->id); ?>">Overwrite existing schedules for this series</label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" value="1" id="skipOverlaps-<?php echo e($s->id); ?>" name="skip_overlaps">
              <label class="form-check-label" for="skipOverlaps-<?php echo e($s->id); ?>">Skip if overlapping schedule exists for the same group</label>
            </div>
            <div class="alert alert-info mt-3">
              Days: <?php echo e(implode(', ', $s->days_of_week ?? [])); ?> | Time: <?php echo e($s->start_time->format('H:i')); ?>–<?php echo e($s->end_time->format('H:i')); ?> | Range: <?php echo e($s->start_date); ?> → <?php echo e($s->end_date); ?>

            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-success px-2">Generate</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<!-- Bulk Generate Modal -->
<div class="modal fade" id="bulkGenerateModal" tabindex="-1" aria-labelledby="bulkGenerateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkGenerateModalLabel">Generate All Series (Current Term)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?php echo e(route('admin.series.generate-all')); ?>">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="bulkOverwrite" name="overwrite">
            <label class="form-check-label" for="bulkOverwrite">Overwrite existing schedules for affected series</label>
          </div>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="bulkSkipOverlaps" name="skip_overlaps">
            <label class="form-check-label" for="bulkSkipOverlaps">Skip if overlapping schedule exists for same group</label>
          </div>
          <div class="alert alert-info mt-3">
            Only series covering today are processed. Uses each series' days/time and date range.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success px-2">Generate All</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if(isset($audits)): ?>
<div class="card mt-4">
  <div class="card-header"><h5 class="mb-0">Generation Audit Log</h5></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>When</th>
            <th>Admin</th>
            <th>Series</th>
            <th>Overwrite</th>
            <th>Skip Overlaps</th>
            <th>Generated Dates</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $audits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $audit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td><?php echo e($audit->created_at); ?></td>
              <td><?php echo e(optional($audit->user)->name ?? 'Unknown'); ?></td>
              <td><?php echo e(optional($audit->series)->name ?? '#'); ?></td>
              <td><?php echo e($audit->overwrite ? 'Yes' : 'No'); ?></td>
              <td><?php echo e($audit->skip_overlaps ? 'Yes' : 'No'); ?></td>
              <td>
                <?php $dates = $audit->generated_dates ?? []; ?>
                <?php echo e(is_array($dates) ? implode(', ', $dates) : $dates); ?>

              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="text-center">No generation activity yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer"><?php echo e($audits->links()); ?></div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\series\index.blade.php ENDPATH**/ ?>