<div class="row">
    <div class="col-md-12"><?php echo $panel; ?></div>

    <?php if($relations->count()): ?>
        <div class="col-md-12 show-relation-container" style="top:10px">
            <?php $__currentLoopData = $relations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $relation->render(); ?>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/show/container.blade.php ENDPATH**/ ?>