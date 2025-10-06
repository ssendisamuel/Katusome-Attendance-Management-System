<form id="attendanceFilters" method="GET" action="<?php echo e(route('admin.attendance.index')); ?>" class="row g-3 mb-3">
  <div class="col-md-3">
    <label class="form-label">Course</label>
    <select name="course_id" class="form-select">
      <option value="">All</option>
      <?php if(isset($courses)): ?>
        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($course->id); ?>" <?php echo e(request('course_id') == $course->id ? 'selected' : ''); ?>><?php echo e($course->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Group</label>
    <select name="group_id" class="form-select">
      <option value="">All</option>
      <?php if(isset($groups)): ?>
        <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($group->id); ?>" <?php echo e(request('group_id') == $group->id ? 'selected' : ''); ?>><?php echo e($group->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Lecturer</label>
    <select name="lecturer_id" class="form-select">
      <option value="">All</option>
      <?php if(isset($lecturers)): ?>
        <?php $__currentLoopData = $lecturers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lecturer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($lecturer->id); ?>" <?php echo e(request('lecturer_id') == $lecturer->id ? 'selected' : ''); ?>><?php echo e($lecturer->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Date</label>
    <input type="date" name="date" value="<?php echo e(request('date')); ?>" class="form-control" />
  </div>
  <div class="col-md-6">
    <label class="form-label">Search by student or name</label>
    <input id="attendanceSearch" type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Student, Course, Group, Lecturer" class="form-control" />
  </div>
  <div class="col-md-6 d-flex align-items-end justify-content-end">
    <a href="<?php echo e(route('admin.attendance.index')); ?>" class="btn btn-outline-secondary me-2">Reset</a>
    <button type="submit" class="btn btn-primary">Filter</button>
  </div>
</form><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\attendance\partials\filters.blade.php ENDPATH**/ ?>