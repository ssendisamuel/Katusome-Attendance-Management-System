
<?php
    $venues = \App\Models\Venue::whereNull('parent_id')->with('children')->orderBy('name')->get();
    $selectedVenueId = old('venue_id', $selectedVenue ?? null);
?>

<label class="form-label">Venue <small class="text-muted">(physical location)</small></label>
<select name="venue_id" class="form-select select2" id="venue-select" data-placeholder="Search venue..."
    data-allow-clear="true">
    <option value="">— Select Venue —</option>
    <?php $__currentLoopData = $venues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($building->children->count()): ?>
            <optgroup label="<?php echo e($building->name); ?>">
                <?php $__currentLoopData = $building->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($room->id); ?>" <?php echo e($selectedVenueId == $room->id ? 'selected' : ''); ?>>
                        <?php echo e($room->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </optgroup>
        <?php else: ?>
            <option value="<?php echo e($building->id); ?>" <?php echo e($selectedVenueId == $building->id ? 'selected' : ''); ?>>
                <?php echo e($building->name); ?>

            </option>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
<?php $__errorArgs = ['venue_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <div class="text-danger small"><?php echo e($message); ?></div>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/katusome.ssendi.dev/resources/views/components/venue-dropdown.blade.php ENDPATH**/ ?>