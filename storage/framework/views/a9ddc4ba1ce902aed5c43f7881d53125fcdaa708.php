<?php if($inline): ?>
<div class="d-flex flex-wrap">
<?php endif; ?>

<?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="vs-checkbox-con vs-checkbox-<?php echo e($style, false); ?>" style="margin-right: <?php echo e($right, false); ?>">
        <input <?php echo in_array($k, $disabled) ? 'disabled' : ''; ?> value="<?php echo e($k, false); ?>" <?php echo $attributes; ?> <?php echo (in_array($k, $checked)) ? 'checked' : ''; ?>>
        <span class="vs-checkbox vs-checkbox-<?php echo e($size, false); ?>">
          <span class="vs-checkbox--check">
            <i class="vs-icon feather icon-check"></i>
          </span>
        </span>
        <?php if($label !== null && $label !== ''): ?>
        <span><?php echo $label; ?></span>
        <?php endif; ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if($inline): ?>
</div>
<?php endif; ?><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/checkbox.blade.php ENDPATH**/ ?>