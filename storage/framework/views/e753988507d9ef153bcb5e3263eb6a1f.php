<?php $__env->startSection('title', 'Bulk Upload Students'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Bulk Upload Students</h4>
  <a href="<?php echo e(route('admin.students.index')); ?>" class="btn btn-outline-secondary">Back</a>
  </div>

<div class="card p-4">
  <p class="mb-3">Upload a CSV file with the following headers: <code>name,email,phone,gender,student_no,reg_no</code>.</p>
  <p class="mb-3">You must select the Program, Group and (optional) Year applied to all uploaded records.</p>
  <a href="<?php echo e(route('admin.students.import.template')); ?>" class="btn btn-sm btn-outline-info mb-4">Download CSV Template</a>

  <form method="POST" action="<?php echo e(route('admin.students.import.process')); ?>" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="row g-4">
      <div class="col-md-4">
        <label class="form-label">Program</label>
        <select name="program_id" id="importProgram" class="form-select" required>
          <option value="">Select Program</option>
          <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($program->id); ?>"><?php echo e($program->name); ?></option>
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
      <div class="col-md-4">
        <label class="form-label">Group</label>
        <select name="group_id" id="importGroup" class="form-select" required>
          <option value="">Select Group</option>
          <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($group->id); ?>"><?php echo e($group->name); ?></option>
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
      <div class="col-md-4">
        <label class="form-label">Year of Study</label>
        <input type="number" min="1" max="10" name="year_of_study" class="form-control" value="<?php echo e(old('year_of_study', 1)); ?>">
        <?php $__errorArgs = ['year_of_study'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
      <div class="col-md-12">
        <label class="form-label">CSV File</label>
        <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
        <?php $__errorArgs = ['file'];
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
      <button class="btn btn-primary">Upload</button>
      <a href="<?php echo e(route('admin.students.index')); ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
  <hr class="my-4">
  <h6>Notes</h6>
  <ul>
    <li><strong>Required:</strong> `name`, `email`, `student_no`.</li>
    <li><strong>Gender:</strong> one of `male`, `female`, `other` or blank.</li>
    <li>Existing students matched by `student_no` are updated, new ones are created.</li>
    <li>Email conflicts with different `student_no` are skipped.</li>
  </ul>
</div>
<?php $__env->startPush('scripts'); ?>
<script>
  (function() {
    const programSelect = document.getElementById('importProgram');
    const groupSelect = document.getElementById('importGroup');
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
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\students\import.blade.php ENDPATH**/ ?>