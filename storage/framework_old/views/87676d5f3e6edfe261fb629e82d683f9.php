<?php $__env->startSection('title', 'Add Schedule'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Add Schedule</h4>
  <a href="<?php echo e(route('admin.schedules.index')); ?>" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card p-4">
  <form action="<?php echo e(route('admin.schedules.store')); ?>" method="POST">
    <?php echo csrf_field(); ?>

    <div class="row g-4">
      <div class="col-md-6">
        <label class="form-label">Course</label>
        <select id="schedule-course" name="course_id" class="form-select" required>
          <option value="">Select Course</option>
          <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($course->id); ?>" data-lecturer-ids="<?php echo e($course->lecturers->pluck('id')->implode(',')); ?>" <?php echo e(old('course_id') == $course->id ? 'selected' : ''); ?>><?php echo e($course->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['course_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select" required>
          <option value="">Select Group</option>
          <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($group->id); ?>" <?php echo e(old('group_id') == $group->id ? 'selected' : ''); ?>><?php echo e($group->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['group_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">Lecturers (optional)</label>
        <select id="schedule-lecturers" name="lecturer_ids[]" class="form-select" multiple>
          <?php $__currentLoopData = $lecturers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lecturer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($lecturer->id); ?>" <?php echo e(collect(old('lecturer_ids', []))->contains($lecturer->id) ? 'selected' : ''); ?>><?php echo e($lecturer->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <div class="form-text">Hold Ctrl/Command to select multiple</div>
        <?php $__errorArgs = ['lecturer_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <?php $__errorArgs = ['lecturer_ids.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">Series (optional)</label>
        <select name="series_id" class="form-select">
          <option value="">Select Series</option>
          <?php $__currentLoopData = $series; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($ser->id); ?>" <?php echo e(old('series_id') == $ser->id ? 'selected' : ''); ?>><?php echo e($ser->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['series_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">Location (optional)</label>
        <input type="text" name="location" class="form-control" value="<?php echo e(old('location')); ?>" placeholder="e.g., Room A1">
        <?php $__errorArgs = ['location'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">Start At</label>
        <input type="datetime-local" name="start_at" class="form-control" value="<?php echo e(old('start_at')); ?>" required>
        <?php $__errorArgs = ['start_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">End At</label>
        <input type="datetime-local" name="end_at" class="form-control" value="<?php echo e(old('end_at')); ?>" required>
        <?php $__errorArgs = ['end_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
    </div>

    <div class="mt-4">
      <button class="btn btn-primary">Save</button>
      <a href="<?php echo e(route('admin.schedules.index')); ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<script>
  (function() {
    const courseSelect = document.getElementById('schedule-course');
    const lecturersSelect = document.getElementById('schedule-lecturers');
    if (!courseSelect || !lecturersSelect) return;

    function setLecturersFromCourseOption(opt) {
      if (!opt) return;
      const csv = (opt.dataset.lecturerIds || '').trim();
      const ids = csv ? csv.split(',').filter(Boolean) : [];
      // If no course lecturers, do not alter selection
      if (ids.length === 0) return;
      // Clear current selection and select course lecturers
      Array.from(lecturersSelect.options).forEach(o => {
        o.selected = ids.includes(String(o.value));
      });
    }

    // On initial load, only preselect if no lecturers selected yet
    const initiallySelectedCount = lecturersSelect.selectedOptions.length;
    if (initiallySelectedCount === 0 && courseSelect.value) {
      setLecturersFromCourseOption(courseSelect.selectedOptions[0]);
    }

    // On course change, always preselect assigned lecturers
    courseSelect.addEventListener('change', function() {
      const opt = courseSelect.selectedOptions[0];
      setLecturersFromCourseOption(opt);
    });
  })();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/admin/schedules/create.blade.php ENDPATH**/ ?>