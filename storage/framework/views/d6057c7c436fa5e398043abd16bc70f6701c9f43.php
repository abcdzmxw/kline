<?php if($showHeader): ?>
    <div class="box-header with-border mb-1" style="padding: .65rem 1rem">
        <h3 class="box-title" style="line-height:30px"><?php echo $form->title(); ?></h3>
        <div class="pull-right"><?php echo $form->renderTools(); ?></div>
    </div>
<?php endif; ?>
<div class="box-body" <?php echo $tabObj->isEmpty() && !$form->hasRows() ? 'style="margin-top: 10px"' : ''; ?> >
    <?php if(!$tabObj->isEmpty()): ?>
        <?php echo $__env->make('admin::form.tab', compact('tabObj', 'form'), \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php else: ?>
        <div class="fields-group">
            <?php if($form->hasRows()): ?>
                <div class="ml-2 mb-2">
                    <input type="hidden" name="_token" value="<?php echo e(csrf_token(), false); ?>">
                    <?php $__currentLoopData = $form->rows(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $row->render(); ?>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php elseif($form->layout()->hasColumns()): ?>
                <?php echo $form->layout()->build(); ?>

            <?php else: ?>
                <?php $__currentLoopData = $form->fields(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $field->render(); ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php echo $form->renderFooter(); ?>


<?php $__currentLoopData = $form->hiddenFields(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php echo $field->render(); ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/container.blade.php ENDPATH**/ ?>