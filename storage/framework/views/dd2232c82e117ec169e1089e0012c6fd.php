<?php $__env->startSection('title', 'Students'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Students</h4>
  <div>
    <a href="<?php echo e(route('admin.students.import.form')); ?>" class="btn btn-outline-primary me-2">Bulk Upload</a>
    <a href="<?php echo e(route('admin.students.create')); ?>" class="btn btn-primary">Add Student</a>
  </div>
</div>
<?php if(session('success')): ?>
  <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Program</th>
          <th>Group</th>
          <th>Year</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <tr>
            <td><?php echo e($student->name); ?></td>
            <td><?php echo e(optional($student->program)->name); ?></td>
            <td><?php echo e(optional($student->group)->name); ?></td>
            <td><?php echo e($student->year_of_study); ?></td>
            <td class="text-end">
              <a href="<?php echo e(route('admin.students.edit', $student)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="<?php echo e(route('admin.students.destroy', $student)); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this student?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?php echo e($students->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\students\index.blade.php ENDPATH**/ ?>