<div>
    <ul class="nav nav-tabs pl-1" style="margin-top: -1rem">
        <?php $__currentLoopData = $tabObj->getTabs(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab['active'] ? 'active' : '', false); ?>" href="#<?php echo e($tab['id'], false); ?>" data-toggle="tab">
                    <?php echo $tab['title']; ?> &nbsp;<i class="feather icon-alert-circle has-tab-error text-danger d-none"></i>
                </a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <div class="tab-content fields-group mt-2" style="padding:18px 0">
        <?php $__currentLoopData = $tabObj->getTabs(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="tab-pane <?php echo e($tab['active'] ? 'active' : '', false); ?>" id="<?php echo e($tab['id'], false); ?>">
                <?php $__currentLoopData = $tab['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $field->render(); ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/tab.blade.php ENDPATH**/ ?>