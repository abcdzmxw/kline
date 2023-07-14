<div class="<?php echo e($viewClass['form-group'], false); ?> <?php echo !$errors->has($errorKey) ? '' : 'has-error'; ?>">

    <label for="<?php echo e($id, false); ?>" class="<?php echo e($viewClass['label'], false); ?> control-label"><?php echo $label; ?></label>

    <div class="<?php echo e($viewClass['field'], false); ?>">

        <?php echo $__env->make('admin::form.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <input name="<?php echo e($name, false); ?>" type="hidden" value="0" />
        <input type="checkbox" name="<?php echo e($name, false); ?>" class="<?php echo e($class, false); ?> la_checkbox" <?php echo e(old($column, $value) == 1 ? 'checked' : '', false); ?> <?php echo $attributes; ?> />

        <?php echo $__env->make('admin::form.help-block', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    </div>
</div>
<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/switchfield.blade.php ENDPATH**/ ?>