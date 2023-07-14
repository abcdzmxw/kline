<div class="<?php echo e($viewClass['form-group'], false); ?> <?php echo !$errors->has($errorKey) ? '' : 'has-error'; ?>">

    <label for="<?php echo e($id, false); ?>" class="<?php echo e($viewClass['label'], false); ?> control-label"><?php echo $label; ?></label>

    <div class="<?php echo e($viewClass['field'], false); ?>">

        <?php echo $__env->make('admin::form.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <textarea class="form-control <?php echo e($class, false); ?>" id="<?php echo e($id, false); ?>" name="<?php echo e($name, false); ?>" placeholder="<?php echo e($placeholder, false); ?>" <?php echo $attributes; ?> ><?php echo e(old($column, $value), false); ?></textarea>

        <?php echo $__env->make('admin::form.help-block', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    </div>
</div>
<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/editor.blade.php ENDPATH**/ ?>