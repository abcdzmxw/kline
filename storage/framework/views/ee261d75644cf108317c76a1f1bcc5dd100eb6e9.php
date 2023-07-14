<div class="input-group input-group-sm">
    <div class="input-group-prepend">
        <span class="input-group-text bg-white text-capitalize"><b><?php echo $label; ?></b></span>
    </div>

    <select class="form-control <?php echo e($class, false); ?>" name="<?php echo e($name, false); ?>" data-value="<?php echo e(request($name, $value), false); ?>" style="width: 100%;">
        <?php if($selectAll): ?>
            <option value=""><?php echo e(trans('admin.all'), false); ?></option>
        <?php endif; ?>
        <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $select => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($select, false); ?>" <?php echo e(Dcat\Admin\Support\Helper::equal($select, request($name, $value)) ?'selected':'', false); ?>><?php echo e($option, false); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/filter/select.blade.php ENDPATH**/ ?>