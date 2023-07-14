
<div class="row">
    <div class="<?php echo e($viewClass['label'], false); ?>"><h4 class="pull-right"><?php echo $label; ?></h4></div>
    <div class="<?php echo e($viewClass['field'], false); ?>"></div>
</div>

<hr style="margin-top: 0px;">

<div class="has-many-<?php echo e($column, false); ?>">

    <div class="has-many-<?php echo e($column, false); ?>-forms">

        <?php $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pk => $form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

            <div class="has-many-<?php echo e($column, false); ?>-form fields-group">

                <?php $__currentLoopData = $form->fields(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $field->render(); ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($options['allowDelete']): ?>
                <div class="form-group row">
                    <label class="<?php echo e($viewClass['label'], false); ?> control-label"></label>
                    <div class="<?php echo e($viewClass['field'], false); ?>">
                        <div class="remove btn btn-white btn-sm pull-right"><i class="feather icon-trash">&nbsp;</i><?php echo e(trans('admin.remove'), false); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <hr>
            </div>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    

    <template class="<?php echo e($column, false); ?>-tpl">
        <div class="has-many-<?php echo e($column, false); ?>-form fields-group">

            <?php echo $template; ?>


            <div class="form-group row">
                <label class="<?php echo e($viewClass['label'], false); ?> control-label"></label>
                <div class="<?php echo e($viewClass['field'], false); ?>">
                    <div class="remove btn btn-white btn-sm pull-right"><i class="feather icon-trash"></i>&nbsp;<?php echo e(trans('admin.remove'), false); ?></div>
                </div>
            </div>
            <hr>
        </div>
    </template>

    <?php if($options['allowCreate']): ?>
    <div class="form-group row">
        <label class="<?php echo e($viewClass['label'], false); ?> control-label"></label>
        <div class="<?php echo e($viewClass['field'], false); ?>">
            <div class="add btn btn-primary btn-outline btn-sm"><i class="feather icon-plus"></i>&nbsp;<?php echo e(trans('admin.new'), false); ?></div>
        </div>
    </div>
    <?php endif; ?>

</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/hasmany.blade.php ENDPATH**/ ?>