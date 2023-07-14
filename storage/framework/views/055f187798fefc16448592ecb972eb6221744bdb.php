<?php ($listErrorKey = "$column.values"); ?>

<div class="<?php echo e($viewClass['form-group'], false); ?> <?php echo e($errors->has($listErrorKey) ? 'has-error' : '', false); ?>">

    <label class="<?php echo e($viewClass['label'], false); ?> control-label"><?php echo e($label, false); ?></label>

    <div class="<?php echo e($viewClass['field'], false); ?>">

        <?php if($errors->has($listErrorKey)): ?>
            <?php $__currentLoopData = $errors->get($listErrorKey); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="control-label" for="inputError"><i class="feather icon-x-circle"></i> <?php echo e($message, false); ?></label><br/>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
        <div class="help-block with-errors"></div>

        <span name="<?php echo e($name, false); ?>"></span>
        <input name="<?php echo e($name, false); ?>[values][<?php echo e(\Dcat\Admin\Form\Field\ListField::DEFAULT_FLAG_NAME, false); ?>]" type="hidden" />

        <table class="table table-hover">

            <tbody class="list-<?php echo e($columnClass, false); ?>-table">

            <?php $__currentLoopData = old("{$column}.values", ($value ?: [])); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <?php ($itemErrorKey = "{$column}.values.{$loop->index}"); ?>

                <tr>
                    <td>
                        <div class="form-group <?php echo e($errors->has($itemErrorKey) ? 'has-error' : '', false); ?>">
                            <div class="col-sm-12">
                                <input name="<?php echo e($name, false); ?>[values][<?php echo e((int) $k, false); ?>]" value="<?php echo e(old("{$column}.values.{$k}", $v), false); ?>" class="form-control" />
                                <div class="help-block with-errors"></div>
                                <?php if($errors->has($itemErrorKey)): ?>
                                    <?php $__currentLoopData = $errors->get($itemErrorKey); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="control-label" for="inputError"><i class="feather icon-x-circle"></i> <?php echo e($message, false); ?></label><br/>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <td style="width: 85px;">
                        <div class="<?php echo e($columnClass, false); ?>-remove btn btn-white btn-sm pull-right">
                            <i class="feather icon-trash">&nbsp;</i><?php echo e(__('admin.remove'), false); ?>

                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td>
                    <div class="<?php echo e($columnClass, false); ?>-add btn btn-primary btn-outline btn-sm pull-right">
                        <i class="feather icon-save"></i>&nbsp;<?php echo e(__('admin.new'), false); ?>

                    </div>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>

<template class="<?php echo e($columnClass, false); ?>-tpl">
    <tr>
        <td>
            <div class="form-group">
                <div class="col-sm-12">
                    <input name="<?php echo e($name, false); ?>[values][{key}]" class="form-control" />
                    <div class="help-block with-errors"></div>
                </div>
            </div>
        </td>

        <td style="width: 85px;">
            <div class="<?php echo e($columnClass, false); ?>-remove btn btn-white btn-sm pull-right">
                <i class="feather icon-trash">&nbsp;</i><?php echo e(__('admin.remove'), false); ?>

            </div>
        </td>
    </tr>
</template><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/listfield.blade.php ENDPATH**/ ?>