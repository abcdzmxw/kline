<?php if($inline): ?>
<div class="d-flex flex-wrap">
<?php endif; ?>

<?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="vs-radio-con vs-radio-success<?php echo e($style, false); ?>" style="margin-right: <?php echo e($right, false); ?>">
        <input <?php echo in_array($k, $disabled) ? 'disabled' : ''; ?> value="<?php echo e($k, false); ?>" <?php echo $attributes; ?> <?php echo \Dcat\Admin\Support\Helper::equal($checked, $k) ? 'checked' : ''; ?>>
        <span class="vs-radio vs-radio-<?php echo e($size, false); ?>">
          <span class="vs-radio--border"></span>
          <span class="vs-radio--circle"></span>
        </span>
        <?php if($label !== null && $label !== ''): ?>
            <span><?php echo $label; ?></span>
        <?php endif; ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if($inline): ?>
</div>
<?php endif; ?><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/radio.blade.php ENDPATH**/ ?>