<div <?php echo $attributes; ?>>
    <div class="card-header d-flex justify-content-between align-items-start pb-0">
        <div>
            <?php if($icon): ?>
            <div class="avatar bg-rgba-<?php echo e($style, false); ?> p-50 m-0">
                <div class="avatar-content">
                    <i class="<?php echo e($icon, false); ?> text-<?php echo e($style, false); ?> font-medium-5"></i>
                </div>
            </div>
            <?php endif; ?>

            <?php if($title): ?>
                <h4 class="card-title mb-1"><?php echo $title; ?></h4>
            <?php endif; ?>

            <div class="metric-header"><?php echo $header; ?></div>
        </div>

        <?php if(! empty($subTitle)): ?>
            <span class="btn btn-sm bg-light shadow-0 p-0">
                <?php echo e($subTitle, false); ?>

            </span>
        <?php endif; ?>

        <?php if(! empty($dropdown)): ?>
        <div class="dropdown chart-dropdown">
            <button class="btn btn-sm btn-light shadow-0 dropdown-toggle p-0 waves-effect" data-toggle="dropdown">
                <?php echo e(current($dropdown), false); ?>

            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <?php $__currentLoopData = $dropdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="dropdown-item"><a href="javascript:void(0)" class="select-option" data-option="<?php echo e($key, false); ?>"><?php echo e($value, false); ?></a></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="metric-content"><?php echo $content; ?></div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/metrics/card.blade.php ENDPATH**/ ?>