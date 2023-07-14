<li class="dd-item" data-id="<?php echo e($branch[$keyName], false); ?>">
    <div class="dd-handle">
        <?php echo $branchCallback($branch); ?>

        <span class="pull-right dd-nodrag">
            <?php if($useEdit): ?>
            <a href="<?php echo e($currentUrl, false); ?>/<?php echo e($branch[$keyName], false); ?>/edit"><i class="feather icon-edit-1"></i>&nbsp;</a>
            <?php endif; ?>

            <?php if($useQuickEdit): ?>
                <a href="javascript:void(0);" data-url="<?php echo e($currentUrl, false); ?>/<?php echo e($branch[$keyName], false); ?>/edit" class="tree-quick-edit"><i class="feather icon-edit"></i></a>
            <?php endif; ?>

            <?php if($useDelete): ?>
            <a href="javascript:void(0);" data-message="ID - <?php echo e($branch[$keyName], false); ?>" data-url="<?php echo e($currentUrl, false); ?>/<?php echo e($branch[$keyName], false); ?>" data-action="delete"><i class="feather icon-trash"></i></a>
            <?php endif; ?>
        </span>
    </div>
    <?php if(isset($branch['children'])): ?>
    <ol class="dd-list">
        <?php $__currentLoopData = $branch['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo $__env->make($branchView, $branch, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ol>
    <?php endif; ?>
</li><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/tree/branch.blade.php ENDPATH**/ ?>