<div class="input-group input-group-sm">
    <?php if($group): ?>
        <div class="input-group-prepend dropdown">
            <a class="filter-group input-group-text bg-white dropdown-toggle" data-toggle="dropdown">
                <span class="<?php echo e($group_name, false); ?>-label"><?php echo e($default['label'], false); ?>&nbsp; </span>
            </a>
            <input type="hidden" name="<?php echo e($id, false); ?>_group" class="<?php echo e($group_name, false); ?>-operation" value="0"/>
            <ul class="dropdown-menu <?php echo e($group_name, false); ?>">
                <?php $__currentLoopData = $group; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="dropdown-item"><a  data-index="<?php echo e($index, false); ?>"> <?php echo e($item['label'], false); ?> </a></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>
    <div class="input-group-prepend">
        <span class="input-group-text bg-white text-capitalize"><b><?php echo $label; ?></b></span>
    </div>
    <input type="<?php echo e($type, false); ?>" class="form-control <?php echo e($id, false); ?>" placeholder="<?php echo e($placeholder, false); ?>" name="<?php echo e($name, false); ?>" value="<?php echo e(request($name, $value), false); ?>">
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/filter/text.blade.php ENDPATH**/ ?>