<div class="<?php echo e($viewClass['form-group'], false); ?> <?php echo !$errors->has($errorKey) ? '' : 'has-error'; ?>">

    <div for="<?php echo e($id, false); ?>" class="<?php echo e($viewClass['label'], false); ?> control-label">
        <span><?php echo $label; ?></span>
    </div>

    <div class="<?php echo e($viewClass['field'], false); ?>">

        <?php echo $__env->make('admin::form.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="input-group">

            <?php if($prepend): ?>
                <span class="input-group-prepend"><span class="input-group-text bg-white"><?php echo $prepend; ?></span></span>
            <?php endif; ?>
            <input <?php echo $attributes; ?> />

            <?php if($append): ?>
                <span class="input-group-append"><?php echo $append; ?></span>
            <?php endif; ?>
        </div>

        <?php echo $__env->make('admin::form.help-block', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/input.blade.php ENDPATH**/ ?>