<?php if($paginator = $grid->paginator()): ?>
    <div class="box-footer clearfix " style="padding-bottom:5px;">
        <?php echo $paginator->render(); ?>

    </div>
<?php else: ?>
    <div class="box-footer clearfix text-80 " style="height:48px;line-height:25px;">
        <?php if($grid->rows()->isEmpty()): ?>
            <?php echo trans('admin.pagination.range', ['first' => '<b>0</b>', 'last' => '<b>'.$grid->rows()->count().'</b>', 'total' => '<b>'.$grid->rows()->count().'</b>',]); ?>

        <?php else: ?>
            <?php echo trans('admin.pagination.range', ['first' => '<b>1</b>', 'last' => '<b>'.$grid->rows()->count().'</b>', 'total' => '<b>'.$grid->rows()->count().'</b>',]); ?>

        <?php endif; ?>
    </div>
<?php endif; ?><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/grid/table-pagination.blade.php ENDPATH**/ ?>