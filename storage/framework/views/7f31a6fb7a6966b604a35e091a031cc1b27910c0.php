<div class="<?php echo e($viewClass['form-group'], false); ?> <?php echo !$errors->has($errorKey) ? '' : 'has-error'; ?>">

    <label for="<?php echo e($id, false); ?>" class="<?php echo e($viewClass['label'], false); ?> control-label"><?php echo $label; ?></label>

    <div class="<?php echo e($viewClass['field'], false); ?>">

        <?php echo $__env->make('admin::form.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="input-group" style="width:100%">
            <input <?php echo e($disabled, false); ?> <?php echo $attributes; ?> name="<?php echo e($name, false); ?>" />

            <div class="jstree-wrapper <?php echo e($class, false); ?>-tree-wrapper">
                <div class="d-flex">
                    <?php echo $checkboxes; ?>

                </div>
                <div class="da-tree" style="margin-top:10px"></div>
            </div>
        </div>

        <?php echo $__env->make('admin::form.help-block', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    </div>
</div>

<?php
    $formId = $formId ? '#'.$formId : '';
?>
<script data-exec-on-popstate>
Dcat.ready(function () {
    var $tree = $('<?php echo $formId; ?> .<?php echo e($class, false); ?>-tree-wrapper').find('.da-tree'),
        opts = <?php echo $options; ?>,
        $input = $('<?php echo $formId; ?> input[name="<?php echo e($name, false); ?>"]'),
        parents = <?php echo $parents; ?>;

    opts.core = opts.core || {};
    opts.core.data = <?php echo $nodes; ?>;

    $(document).on("click", "<?php echo $formId; ?> .<?php echo e($class, false); ?>-tree-wrapper input[value=1]", function () {
        $tree.jstree($(this).prop("checked") ? "check_all" : "uncheck_all");
    });
    $(document).on("click", "<?php echo $formId; ?> .<?php echo e($class, false); ?>-tree-wrapper input[value=2]", function () {
        $tree.jstree($(this).prop("checked") ? "open_all" : "close_all");
    });

    $tree.on("changed.jstree", function (e, data) {
        $input.val('');

        var i, selected = [];
        for (i in data.selected) {
            if (Dcat.helpers.inObject(parents, data.selected[i])) { // 过滤父节点
                continue;
            }
            selected.push(data.selected[i]);
        }

        selected.length && $input.val(selected.join(','));
    }).on("loaded.jstree", function () {
        <?php if($expand): ?> $tree.jstree('open_all'); <?php endif; ?>
    }).jstree(opts);

});
</script><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/form/tree.blade.php ENDPATH**/ ?>