<?php if($title || $tools): ?>
<div class="box-header with-border" style="padding: .65rem 1rem">
    <h3 class="box-title" style="line-height:30px;"><?php echo $title; ?></h3>
    <div class="pull-right"><?php echo $tools; ?></div>
</div>
<?php endif; ?>
<div class="box-body">
    <div class="form-horizontal mt-1">
        <?php if($rows->isEmpty()): ?>
            <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $field->render(); ?>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div>
                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $row->render(); ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        <div class="clearfix"></div>
    </div>
</div>
<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/show/panel.blade.php ENDPATH**/ ?>