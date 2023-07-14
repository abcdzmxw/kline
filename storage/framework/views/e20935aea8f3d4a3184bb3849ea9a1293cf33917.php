<?php if(! $isHoldSelectAllCheckbox): ?>
<div class="btn-group dropdown  <?php echo e($selectAllName, false); ?>-btn" style="display:none;margin-right: 3px;z-index: 100">
    <button type="button" class="btn btn-white dropdown-toggle btn-mini" data-toggle="dropdown">
        <span class="d-none d-sm-inline selected"></span>
        <span class="caret"></span>
        <span class="sr-only"></span>
    </button>
    <ul class="dropdown-menu" role="menu">
        <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="dropdown-item">
                <?php echo $action->render(); ?>

            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/grid/batch-actions.blade.php ENDPATH**/ ?>