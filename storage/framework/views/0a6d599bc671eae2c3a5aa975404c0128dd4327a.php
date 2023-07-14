
<div class="row form-group">
    <div class="<?php echo e($viewClass['label'], false); ?> "><label class="control-label pull-right"><?php echo $label; ?></label></div>
    <div class="<?php echo e($viewClass['field'], false); ?>">
        <?php echo $__env->make('admin::form.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <span name="<?php echo e($column, false); ?>"></span> 

        <div class="has-many-<?php echo e($column, false); ?>" >
            <table class="table table-has-many has-many-<?php echo e($column, false); ?>">
                <thead>
                <tr>
                    <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th><?php echo e($header, false); ?></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <th class="hidden"></th>

                    <?php if($options['allowDelete']): ?>
                        <th></th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody class="has-many-<?php echo e($column, false); ?>-forms">
                <?php $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pk => $form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="has-many-<?php echo e($column, false); ?>-form fields-group">

                        <?php $hidden = ''; ?>

                        <?php $__currentLoopData = $form->fields(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                            <?php if(is_a($field, \Dcat\Admin\Form\Field\Hidden::class)): ?>
                                <?php $hidden .= $field->render(); ?>
                                <?php continue; ?>
                            <?php endif; ?>

                            <td><?php echo $field->setLabelClass(['hidden'])->width(12, 0)->render(); ?></td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <td class="hidden"><?php echo $hidden; ?></td>

                        <?php if($options['allowDelete']): ?>
                            <td class="form-group">
                                <div>
                                    <div class="remove btn btn-white btn-sm pull-right"><i class="feather icon-trash"></i></div>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>

            <template class="<?php echo e($column, false); ?>-tpl">
                <tr class="has-many-<?php echo e($column, false); ?>-form fields-group">

                    <?php echo $template; ?>


                    <td class="form-group">
                        <div>
                            <div class="remove btn btn-white btn-sm pull-right"><i class="feather icon-trash"></i></div>
                        </div>
                    </td>
                </tr>
            </template>

            <?php if($options['allowCreate']): ?>
                <div class="form-group row m-t-10">
                    <div class="<?php echo e($viewClass['field'], false); ?>" style="margin-top: 8px">
                        <div class="add btn btn-primary btn-outline btn-sm"><i class="feather icon-plus"></i>&nbsp;<?php echo e(trans('admin.new'), false); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/hasmanytable.blade.php ENDPATH**/ ?>