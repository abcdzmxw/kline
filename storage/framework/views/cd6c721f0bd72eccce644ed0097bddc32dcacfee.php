
<div class="dcat-box custom-data-table dt-bootstrap4">

    <?php echo $__env->make('admin::grid.table-toolbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php echo $grid->renderFilter(); ?>


    <?php echo $grid->renderHeader(); ?>


    <div class="table-responsive table-wrapper <?php echo e($grid->option('table_collapse') ? 'table-collapse' : '', false); ?>">
            <table class="custom-data-table dataTable <?php echo e($grid->formatTableClass(), false); ?>" id="<?php echo e($tableId, false); ?>">
                <thead>
                <?php if($headers = $grid->getComplexHeaders()): ?>
                    <tr>
                        <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $header->render(); ?>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endif; ?>
                <tr>
                    <?php $__currentLoopData = $grid->columns(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th <?php echo $column->formatTitleAttributes(); ?>><?php echo $column->getLabel(); ?><?php echo $column->renderHeader(); ?></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
                </thead>

                <?php if($grid->hasQuickCreate()): ?>
                    <?php echo $grid->renderQuickCreate(); ?>

                <?php endif; ?>

                <tbody>
                <?php $__currentLoopData = $grid->rows(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr <?php echo $row->rowAttributes(); ?>>
                        <?php $__currentLoopData = $grid->getColumnNames(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td <?php echo $row->columnAttributes($name); ?>>
                                <?php echo $row->column($name); ?>

                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if($grid->rows()->isEmpty()): ?>
                    <tr>
                        <td colspan="<?php echo count($grid->getColumnNames()); ?>">
                            <div style="margin:5px 0 0 10px;"><span class="help-block" style="margin-bottom:0"><i class="feather icon-alert-circle"></i>&nbsp;<?php echo e(trans('admin.no_data'), false); ?></span></div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php echo $grid->renderFooter(); ?>


    <?php echo $__env->make('admin::grid.table-pagination', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

</div>
<?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/grid/data-table.blade.php ENDPATH**/ ?>