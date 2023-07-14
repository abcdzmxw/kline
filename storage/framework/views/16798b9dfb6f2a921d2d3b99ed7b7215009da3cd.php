<div class="show-field form-group row">
    <div class="col-sm-2 control-label">
        <span><?php echo e($label, false); ?></span>
    </div>

    <div class="col-sm-<?php echo e($width, false); ?>">
        <?php if($wrapped): ?>
            <div class="box box-solid box-default no-margin box-show">
                <div class="box-body">
                    <?php if($escape): ?>
                        <?php echo e($content, false); ?>

                    <?php else: ?>
                        <?php echo $content; ?>

                    <?php endif; ?>
                    &nbsp;
                </div>
            </div>
        <?php else: ?>
            <?php if($escape): ?>
                <?php echo e($content, false); ?>

            <?php else: ?>
                <?php echo $content; ?>

            <?php endif; ?>
        <?php endif; ?>
    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/show/field.blade.php ENDPATH**/ ?>