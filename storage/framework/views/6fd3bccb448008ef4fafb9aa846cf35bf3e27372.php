<div class="<?php echo e($viewClass['form-group'], false); ?> <?php echo !$errors->has($column) ?: 'has-error'; ?>" >

    <label for="<?php echo e($id, false); ?>" class="<?php echo e($viewClass['label'], false); ?> control-label pt-0"><?php echo $label; ?></label>

    <div class="<?php echo e($viewClass['field'], false); ?>" id="<?php echo e($id, false); ?>">

        <?php if($checkAll): ?>
            <?php echo $checkAll; ?>

            <hr style="margin-top: 10px;margin-bottom: 0;">
        <?php endif; ?>

        <?php echo $__env->make('admin::form.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <?php echo $checkbox; ?>


        <input type="hidden" name="<?php echo e($name, false); ?>[]">

        <?php echo $__env->make('admin::form.help-block', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    </div>
</div>
<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/checkbox.blade.php ENDPATH**/ ?>