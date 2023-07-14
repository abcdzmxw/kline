<div <?php echo $attributes; ?>>
    <div class="box-header with-border">
        <h3 class="box-title"><?php echo $title; ?></h3>
        <div class="box-tools pull-right">
            <?php $__currentLoopData = $tools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tool): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $tool; ?>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <div class="box-body collapse show" style="<?php echo $padding; ?>">
        <?php echo $content; ?>

    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/box.blade.php ENDPATH**/ ?>