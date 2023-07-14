<?php echo $start; ?>

    <div class="box-body fields-group p-0 pt-1">
        <?php if(! $tabObj->isEmpty()): ?>
            <?php echo $__env->make('admin::form.tab', compact('tabObj'), \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($field instanceof \Dcat\Admin\Form\Field\Hidden): ?>
                    <?php echo $field->render(); ?>

                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <?php if($rows): ?>
                <div class="ml-2 mb-2">
                    <input type="hidden" name="_token" value="<?php echo e(csrf_token(), false); ?>">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $row->render(); ?>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($field instanceof \Dcat\Admin\Form\Field\Hidden): ?>
                            <?php echo $field->render(); ?>

                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php elseif($layout): ?>
                <?php echo $layout->build(); ?>

            <?php else: ?>
                <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $field->render(); ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if($method != 'GET'): ?>
        <input type="hidden" name="_token" value="<?php echo e(csrf_token(), false); ?>">
    <?php endif; ?>
    
    <!-- /.box-body -->
    <?php if($buttons['submit'] || $buttons['reset']): ?>
    <div class="box-footer row" style="display: flex">
        <div class="col-md-<?php echo e($width['label'], false); ?>"> &nbsp;</div>

        <div class="col-md-<?php echo e($width['field'], false); ?>">
            <?php if(! empty($buttons['reset'])): ?>
                <button type="reset" class="btn btn-white pull-left"><i class="feather icon-rotate-ccw"></i> <?php echo e(trans('admin.reset'), false); ?></button>
            <?php endif; ?>

            <?php if(! empty($buttons['submit'])): ?>
                <button type="submit" class="btn btn-primary pull-right"><i class="feather icon-save"></i> <?php echo e(trans('admin.submit'), false); ?></button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
<?php echo $end; ?>

<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/form.blade.php ENDPATH**/ ?>