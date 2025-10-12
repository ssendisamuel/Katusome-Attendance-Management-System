<?php $__env->startSection('title', 'Schedules'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Schedules</h4>
  <a href="<?php echo e(route('admin.schedules.create')); ?>" class="btn btn-primary">Add Schedule</a>
  </div>
<?php if(session('success')): ?>
  <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<div class="card">
  <div class="card-body">
    <div id="schedulesFiltersWrap">
      <?php echo $__env->make('admin.schedules.partials.filters', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
  </div>
  <div id="schedulesTable">
    <?php echo $__env->make('admin.schedules.partials.table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </div>
</div>
<script>
  (function() {
    const filtersWrap = document.getElementById('schedulesFiltersWrap');
    const tableWrap = document.getElementById('schedulesTable');
    let form = filtersWrap.querySelector('#scheduleFilters');
    let debounceTimer;

    function urlFromForm(){
      const params = new URLSearchParams(new FormData(form));
      return form.action + (params.toString() ? ('?' + params.toString()) : '');
    }

    function attachPagination(){
      tableWrap.querySelectorAll('.pagination a').forEach(a => {
        a.addEventListener('click', function(e){
          e.preventDefault();
          updateTable(this.href);
        });
      });
    }

    function bindDeleteButtons(){
      tableWrap.querySelectorAll('.js-delete-schedule').forEach(function(btn){
        btn.addEventListener('click', function(e){
          e.preventDefault();
          const form = this.closest('form');
          const name = this.dataset.name || 'this schedule';
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
    }

    function updateTable(url){
      const target = url || urlFromForm();
      fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.text())
        .then(html => {
          tableWrap.innerHTML = html;
          attachPagination();
          bindDeleteButtons();
          history.replaceState(null, '', target);
        })
        .catch(console.error);
    }

    function refreshFilters(){
      const params = new URLSearchParams(new FormData(form));
      params.set('fragment', 'filters');
      const target = form.action + '?' + params.toString();
      fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.text())
        .then(html => {
          filtersWrap.innerHTML = html;
          form = filtersWrap.querySelector('#scheduleFilters');
          bindFilterEvents();
        })
        .catch(console.error);
    }

    attachPagination();
    bindDeleteButtons();

    function bindFilterEvents(){
      const searchInput = document.getElementById('scheduleSearch');
      form.addEventListener('submit', function(e){
        e.preventDefault();
        updateTable();
      });

      const courseEl = form.querySelector('[name="course_id"]');
      if (courseEl) courseEl.addEventListener('change', () => { refreshFilters(); updateTable(); });

      ['group_id','lecturer_id','date'].forEach(name => {
        const el = form.querySelector(`[name="${name}"]`);
        if (el) el.addEventListener('change', () => updateTable());
      });

      if (searchInput) {
        searchInput.addEventListener('input', function(){
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(function(){
            updateTable();
          }, 400);
        });
      }

      const resetLink = form.querySelector('a.btn.btn-outline-secondary');
      if (resetLink) {
        resetLink.addEventListener('click', function(e){
          e.preventDefault();
          form.reset();
          refreshFilters();
          updateTable(this.href);
        });
      }
    }

    bindFilterEvents();
  })();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/admin/schedules/index.blade.php ENDPATH**/ ?>