<div class="box-footer">

    <div class="col-md-<?php echo e($width['label'], false); ?> d-md-block" style="display: none"></div>

    <div class="col-md-<?php echo e($width['field'], false); ?>">

        <?php if(! empty($buttons['submit'])): ?>
            <div class="btn-group pull-right">
                <button class="btn btn-primary submit"><i class="feather icon-save"></i> <?php echo e(trans('admin.submit'), false); ?></button>
            </div>

            <?php if($checkboxes): ?>
                <div class="pull-right d-md-flex" style="margin:10px 15px 0 0;display: none"><?php echo $checkboxes; ?></div>
            <?php endif; ?>

        <?php endif; ?>

        <?php if(! empty($buttons['reset'])): ?>
        <div class="btn-group pull-left">
            <button type="reset" class="btn btn-white"><i class="feather icon-rotate-ccw"></i> <?php echo e(trans('admin.reset'), false); ?></button>
        </div>
        <?php endif; ?>
    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/footer.blade.php ENDPATH**/ ?>