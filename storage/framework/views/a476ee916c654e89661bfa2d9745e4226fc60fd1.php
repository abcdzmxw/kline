<div <?php echo $attributes; ?> >
    <?php if($showCloseBtn): ?>
    <button type="button" class="close" data-dismiss="alert">×</button>
    <?php endif; ?>
    <?php if($title): ?>
    <h4><?php if(! empty($icon)): ?><i class="<?php echo e($icon, false); ?>"></i>&nbsp;<?php endif; ?> <?php echo $title; ?></h4>
    <?php endif; ?>
    <?php echo $content; ?>

</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/alert.blade.php ENDPATH**/ ?>