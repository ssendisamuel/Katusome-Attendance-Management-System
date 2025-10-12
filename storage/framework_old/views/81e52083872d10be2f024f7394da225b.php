<?php $__env->startSection('title', 'Add Schedule Series'); ?>

<?php $__env->startSection('content'); ?>
<h4 class="mb-4">Add Schedule Series</h4>
<div class="card p-4">
  <form method="POST" action="<?php echo e(route('admin.series.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required>
      <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Course</label>
        <select id="series-create-course" name="course_id" class="form-select" required>
          <option value="">Select Course</option>
          <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($course->id); ?>" data-lecturer-ids="<?php echo e($course->lecturers->pluck('id')->implode(',')); ?>" <?php if(old('course_id')==$course->id): echo 'selected'; endif; ?>><?php echo e($course->name); ?></option>
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
      <div class="col-md-4 mb-3">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select" required>
          <option value="">Select Group</option>
          <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($group->id); ?>" <?php if(old('group_id')==$group->id): echo 'selected'; endif; ?>><?php echo e($group->name); ?></option>
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
      <div class="col-md-4 mb-3">
        <label class="form-label">Lecturer (optional)</label>
        <select id="series-create-lecturer" name="lecturer_id" class="form-select">
          <option value="">None</option>
          <?php $__currentLoopData = $lecturers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lecturer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($lecturer->id); ?>" <?php if(old('lecturer_id')==$lecturer->id): echo 'selected'; endif; ?>><?php echo e($lecturer->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['lecturer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?php echo e(old('start_date')); ?>" required>
        <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?php echo e(old('end_date')); ?>" required>
        <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Start Time</label>
        <input type="time" name="start_time" class="form-control" value="<?php echo e(old('start_time')); ?>" required>
        <?php $__errorArgs = ['start_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">End Time</label>
        <input type="time" name="end_time" class="form-control" value="<?php echo e(old('end_time')); ?>" required>
        <?php $__errorArgs = ['end_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Days of Week</label>
      <?php
        $selectedDays = collect(old('days_of_week', []))
          ->map(function ($d) { return strtolower(trim($d)); });
        $allDays = ['mon','tue','wed','thu','fri','sat','sun'];
        $selectedLabel = $selectedDays->isEmpty()
          ? 'Select days'
          : $selectedDays->map(fn($d) => strtoupper($d))->implode(', ');
      ?>
      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
          <?php echo e($selectedLabel); ?>

        </button>
        <div class="dropdown-menu w-100 p-3">
          <div class="row">
            <?php $__currentLoopData = $allDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="col-6 mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="days_of_week[]" value="<?php echo e($day); ?>" id="create-day-<?php echo e($day); ?>" <?php if($selectedDays->contains($day)): echo 'checked'; endif; ?>>
                  <label class="form-check-label" for="create-day-<?php echo e($day); ?>"><?php echo e(strtoupper($day)); ?></label>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      </div>
      <?php $__errorArgs = ['days_of_week'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="is_recurring" <?php if(old('is_recurring')): echo 'checked'; endif; ?>>
      <label class="form-check-label" for="is_recurring">Recurring</label>
    </div>
    <div class="mb-3">
      <label class="form-label">Location (optional)</label>
      <input type="text" name="location" class="form-control" value="<?php echo e(old('location')); ?>">
      <?php $__errorArgs = ['location'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <button class="btn btn-primary">Save</button>
    <a href="<?php echo e(route('admin.series.index')); ?>" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php $__env->stopSection(); ?>
<script>
  (function() {
    const courseSelect = document.getElementById('series-create-course');
    const lecturerSelect = document.getElementById('series-create-lecturer');
    if (!courseSelect || !lecturerSelect) return;

    function preselectFirstLecturer(opt) {
      if (!opt) return;
      const csv = (opt.dataset.lecturerIds || '').trim();
      const ids = csv ? csv.split(',').filter(Boolean) : [];
      if (ids.length === 0) return;
      // For series (single lecturer), select the first assigned lecturer if none chosen yet
      const first = ids[0];
      if (!first) return;
      Array.from(lecturerSelect.options).forEach(o => {
        o.selected = String(o.value) === String(first);
      });
    }

    // Only auto-select if no lecturer currently selected
    const hasSelection = Array.from(lecturerSelect.options).some(o => o.selected && o.value);
    if (!hasSelection && courseSelect.value) {
      preselectFirstLecturer(courseSelect.selectedOptions[0]);
    }

    courseSelect.addEventListener('change', function() {
      // On change, if user hasn't selected anything, set first lecturer
      const userHasSelected = Array.from(lecturerSelect.options).some(o => o.selected && o.value);
      if (!userHasSelected) {
        preselectFirstLecturer(courseSelect.selectedOptions[0]);
      }
    });
  })();
</script>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/admin/series/create.blade.php ENDPATH**/ ?>