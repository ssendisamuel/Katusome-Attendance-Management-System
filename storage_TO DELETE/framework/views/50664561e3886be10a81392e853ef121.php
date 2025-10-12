<?php $__env->startSection('title', 'Lecturers'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Lecturers</h4>
  <a href="<?php echo e(route('admin.lecturers.create')); ?>" class="btn btn-primary">Add Lecturer</a>
</div>
<?php if(session('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo e(session('success')); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if(session('info')): ?>
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    <?php echo e(session('info')); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<div class="card">
  <div class="card-body">
    <form id="lecturerFilters" method="GET" action="<?php echo e(route('admin.lecturers.index')); ?>" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Search by name, email or phone</label>
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" class="form-control" placeholder="e.g. John, 077..." />
      </div>
      <div class="col-md-6 d-flex align-items-end justify-content-end">
        <a href="<?php echo e(route('admin.lecturers.index')); ?>" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-export="print" data-export-target="#lecturersTableEl"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
        <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#lecturersTableEl" data-title="Lecturers" data-filename="Lecturers.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Lecturers" data-json-url="<?php echo e(route('admin.lecturers.index', array_merge(request()->query(), ['format' => 'json']))); ?>"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
        <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#lecturersTableEl" data-title="Lecturers" data-filename="Lecturers.xlsx" data-json-url="<?php echo e(route('admin.lecturers.index', array_merge(request()->query(), ['format' => 'json']))); ?>"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table" id="lecturersTableEl">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $lecturers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lecturer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <tr>
            <td><?php echo e($lecturer->name); ?></td>
            <td><?php echo e($lecturer->email); ?></td>
            <td><?php echo e($lecturer->phone); ?></td>
            <td class="text-end">
              <a href="<?php echo e(route('admin.lecturers.edit', $lecturer)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="<?php echo e(route('admin.lecturers.destroy', $lecturer)); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger js-delete-lecturer" data-name="<?php echo e($lecturer->name); ?>">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?php echo e($lecturers->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/report-export.js']); ?>
<script>
  (function () {
    if (window.Toast) {
      <?php if(session('success')): ?>
        window.Toast.fire({ icon: 'success', title: <?php echo json_encode(session('success'), 15, 512) ?> });
      <?php endif; ?>
      <?php if(session('info')): ?>
        window.Toast.fire({ icon: 'info', title: <?php echo json_encode(session('info'), 15, 512) ?> });
      <?php endif; ?>
      <?php if(session('error')): ?>
        window.Toast.fire({ icon: 'error', title: <?php echo json_encode(session('error'), 15, 512) ?> });
      <?php endif; ?>
    }

    document.querySelectorAll('.js-delete-lecturer').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const name = this.dataset.name || 'this lecturer';
        if (window.Swal && window.Swal.fire) {
          window.Swal.fire({
            title: 'Delete ' + name + '?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
          }).then(function (result) {
            if (result.isConfirmed) form.submit();
          });
        } else {
          if (confirm('Delete ' + name + '?')) form.submit();
        }
      });
    });
  })();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/admin/lecturers/index.blade.php ENDPATH**/ ?>