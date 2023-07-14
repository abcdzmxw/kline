<div class="input-group input-group-sm">
    <div class="input-group-prepend">
        <span class="input-group-text bg-white text-capitalize"><b><?php echo $label; ?></b></span>
    </div>

    <select class="form-control <?php echo e($class, false); ?>" name="<?php echo e($name, false); ?>[]" multiple style="width: 100%;">
        <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $select => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($select, false); ?>" <?php echo e(in_array((string)$select, (array) $value)  ?'selected':'', false); ?>><?php echo e($option, false); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/filter/multipleselect.blade.php ENDPATH**/ ?>