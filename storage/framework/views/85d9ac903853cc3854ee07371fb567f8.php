# <?php echo e($exception->class()); ?> - <?php echo $exception->title(); ?>

<?php echo $exception->message(); ?>


PHP <?php echo e(PHP_VERSION); ?>

Laravel <?php echo e(app()->version()); ?>

<?php echo e($exception->request()->httpHost()); ?>


## Stack Trace

<?php $__currentLoopData = $exception->frames(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $frame): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php echo e($index); ?> - <?php echo e($frame->file()); ?>:<?php echo e($frame->line()); ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

## Request

<?php echo e($exception->request()->method()); ?> <?php echo e(\Illuminate\Support\Str::start($exception->request()->path(), '/')); ?>


## Headers
<?php $headers = $exception->requestHeaders(); ?>
<?php if(!empty($headers)): ?>
<?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
* **<?php echo e($key); ?>**: <?php echo $value; ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php else: ?>
No header data available.
<?php endif; ?>

## Route Context
<?php $routeContext = $exception->applicationRouteContext(); ?>
<?php if(!empty($routeContext)): ?>
<?php $__currentLoopData = $routeContext; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php echo e($name); ?>: <?php echo $value; ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php else: ?>
No routing data available.
<?php endif; ?>

## Route Parameters

<?php if($routeParametersContext = $exception->applicationRouteParametersContext()): ?>
<?php echo $routeParametersContext; ?>

<?php else: ?>
No route parameter data available.
<?php endif; ?>

## Database Queries
<?php $queries = $exception->applicationQueries(); ?>
<?php if(!empty($queries)): ?>
<?php $__currentLoopData = $queries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php $connectionName = $q['connectionName'] ?? 'default'; $sql = $q['sql'] ?? ''; $time = $q['time'] ?? 0; ?>
* <?php echo e($connectionName); ?> - <?php echo $sql; ?> (<?php echo e($time); ?> ms)
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php else: ?>
No database queries detected.
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\vendor\laravel\framework\src\Illuminate\Foundation\resources\exceptions\renderer\markdown.blade.php ENDPATH**/ ?>