<?php
    $active = $builder->isActive($item);

    $layer = $item['layer'] ?? 0;
?>

<?php if($builder->visible($item)): ?>
    <?php if(isset($item['is_header'])): ?>
        <li class="nav-header">
            <?php echo e($builder->translate($item['title']), false); ?>

        </li>
    <?php elseif(! isset($item['children'])): ?>
        <li class="nav-item">
            <a <?php if(mb_strpos($item['uri'], '://') !== false): ?> target="_blank" <?php endif; ?> href="<?php echo e($builder->getUrl($item['uri']), false); ?>" class="nav-link <?php echo $builder->isActive($item) ? 'active' : ''; ?>">
                <?php echo str_repeat('&nbsp;', $layer); ?><i class="fa <?php echo e($item['icon'] ?: 'feather icon-circle', false); ?>"></i>
                <p>
                    <?php echo e($builder->translate($item['title']), false); ?>

                </p>
            </a>
        </li>
    <?php else: ?>
        <?php
            $active = $builder->isActive($item);
        ?>

        <li class="nav-item has-treeview <?php echo e($active ? 'menu-open' : '', false); ?>">
            <a href="#" class="nav-link">
                <?php echo str_repeat('&nbsp;', $layer); ?><i class="fa <?php echo e($item['icon'] ?: 'feather icon-circle', false); ?>"></i>
                <p>
                    <?php echo e($builder->translate($item['title']), false); ?>

                    <i class="right fa fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <?php $__currentLoopData = $item['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $item['layer'] = $layer + 1;
                    ?>

                    <?php echo $__env->make('admin::partials.menu', $item, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </li>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/partials/menu.blade.php ENDPATH**/ ?>