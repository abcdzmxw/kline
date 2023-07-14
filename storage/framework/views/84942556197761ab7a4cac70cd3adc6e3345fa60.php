<div id="<?php echo e($containerId, false); ?>" class="<?php echo e($viewClass['form-group'], false); ?> <?php echo !$errors->has($errorKey) ? '' : 'has-error'; ?>">

    <label for="<?php echo e($column, false); ?>" class="<?php echo e($viewClass['label'], false); ?> control-label"><?php echo $label; ?></label>

    <div class="<?php echo e($viewClass['field'], false); ?>">

        <?php echo $__env->make('admin::form.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <input name="<?php echo e($name, false); ?>" id="<?php echo e($id, false); ?>" type="hidden" />

        <div class="web-uploader <?php echo e($fileType, false); ?>">
            <div class="queueList">
                <div class="placeholder dnd-area">
                    <div class="file-picker"></div>
                    <p><?php echo e(trans('admin.uploader.drag_file'), false); ?></p>
                </div>
            </div>
            <div class="statusBar" style="display:none;">
                <div class="upload-progress progress progress-bar-primary pull-left">
                    <div class="progress-bar progress-bar-striped active" style="line-height:18px">0%</div>
                </div>
                <div class="info"></div>
                <div class="btns">
                    <div class="add-file-button"></div>
                    <?php if($showUploadBtn): ?>
                    &nbsp;
                    <div class="upload-btn btn btn-primary"><i class="feather icon-upload"></i> &nbsp;<?php echo e(trans('admin.upload'), false); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php echo $__env->make('admin::form.help-block', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/file.blade.php ENDPATH**/ ?>