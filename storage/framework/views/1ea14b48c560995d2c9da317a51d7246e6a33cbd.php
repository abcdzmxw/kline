<div class="filter-box card p-2 <?php echo e($expand ? '' : 'd-none', false); ?> <?php echo e($containerClass, false); ?>" style="padding-bottom: .5rem!important;margin-top: 10px;margin-bottom: 8px;box-shadow: 0 2px 3px 0 rgba(0, 0, 0, 0.04);">
    <div class="card-body" style="<?php echo $style; ?>"  id="<?php echo e($filterID, false); ?>">
        <form action="<?php echo $action; ?>" class="form-horizontal" pjax-container method="get">
            <div class="btn-group">
                <button class="btn btn-primary btn-sm btn-mini submit">
                    <i class="feather icon-search"></i><span class="d-none d-sm-inline">&nbsp;&nbsp;<?php echo e(trans('admin.search'), false); ?></span>
                </button>
            </div>
            <div class="btn-group btn-group-sm default btn-mini" style="margin-left:5px"  >
                <?php if(!$disableResetButton): ?>
                    <a  href="<?php echo $action; ?>" class="reset btn btn-white btn-sm ">
                        <i class="feather icon-rotate-ccw"></i><span class="d-none d-sm-inline">&nbsp;&nbsp;<?php echo e(trans('admin.reset'), false); ?></span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="row mt-1 mb-0">
                <?php $__currentLoopData = $layout->columns(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $__currentLoopData = $column->filters(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $filter->render(); ?>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

        </form>
    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/filter/container.blade.php ENDPATH**/ ?>