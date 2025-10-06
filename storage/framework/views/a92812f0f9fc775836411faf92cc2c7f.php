<?php $__env->startSection('title', 'Add Student'); ?>

<?php $__env->startSection('content'); ?>
<h4 class="mb-4">Add Student</h4>
<div class="card p-4">
  <form method="POST" action="<?php echo e(route('admin.students.store')); ?>">
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
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" required>
      <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone')); ?>">
      <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Student No.</label>
      <input type="text" name="student_no" class="form-control" value="<?php echo e(old('student_no')); ?>" required>
      <?php $__errorArgs = ['student_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Reg No.</label>
      <input type="text" name="reg_no" class="form-control" value="<?php echo e(old('reg_no')); ?>">
      <?php $__errorArgs = ['reg_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Gender</label>
      <select name="gender" class="form-select">
        <option value="">Select Gender</option>
        <option value="male" <?php if(old('gender')=='male'): echo 'selected'; endif; ?>>Male</option>
        <option value="female" <?php if(old('gender')=='female'): echo 'selected'; endif; ?>>Female</option>
        <option value="other" <?php if(old('gender')=='other'): echo 'selected'; endif; ?>>Other</option>
      </select>
      <?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Program</label>
      <select name="program_id" id="studentProgram" class="form-select" required>
        <option value="">Select Program</option>
        <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($program->id); ?>" <?php if(old('program_id')==$program->id): echo 'selected'; endif; ?>><?php echo e($program->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
      <?php $__errorArgs = ['program_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Group</label>
      <select name="group_id" id="studentGroup" class="form-select" required>
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
    <div class="mb-3">
      <label class="form-label">Year of Study</label>
      <input type="number" min="1" max="10" name="year_of_study" class="form-control" value="<?php echo e(old('year_of_study')); ?>">
      <?php $__errorArgs = ['year_of_study'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <button class="btn btn-primary">Save</button>
    <a href="<?php echo e(route('admin.students.index')); ?>" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php $__env->startPush('scripts'); ?>
<script>
  (function() {
    const programSelect = document.getElementById('studentProgram');
    const groupSelect = document.getElementById('studentGroup');
    async function fetchGroups(programId) {
      const url = `${window.location.origin}/admin/programs/${programId}/groups`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) return [];
      return res.json();
    }
    async function refreshGroups() {
      const pid = programSelect.value;
      groupSelect.innerHTML = '<option value="">Select Group</option>';
      if (!pid) return;
      const groups = await fetchGroups(pid);
      groups.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g.id;
        opt.textContent = g.name;
        groupSelect.appendChild(opt);
      });
    }
    programSelect?.addEventListener('change', refreshGroups);
  })();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\students\create.blade.php ENDPATH**/ ?>