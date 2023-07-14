<div <?php echo $attributes; ?>>
    <?php if($title || $tools): ?>
        <div class="card-header <?php echo e($divider ? 'with-border' : '', false); ?>">
            <span class="card-box-title"><?php echo $title; ?></span>
            <div class="box-tools pull-right">
                <?php $__currentLoopData = $tools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tool): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $tool; ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="card-body" style="<?php echo $padding; ?>">
        <?php echo $content; ?>

    </div>
    <?php if($footer): ?>
    <div class="card-footer">
        <?php echo $footer; ?>

    </div>
    <?php endif; ?>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/card.blade.php ENDPATH**/ ?>