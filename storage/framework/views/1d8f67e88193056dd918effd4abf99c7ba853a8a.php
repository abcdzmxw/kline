<div  <?php echo $attributes; ?>><div class="da-tree"></div></div>

<script>
Dcat.ready(function () {
    var opts = <?php echo json_encode($options); ?>, tree = $('#<?php echo e($id, false); ?>').find('.da-tree');

    opts.core.data = <?php echo json_encode($nodes); ?>;

    tree.on("loaded.jstree", function () {
        tree.jstree('open_all');
    }).jstree(opts);
});
</script><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/widgets/tree.blade.php ENDPATH**/ ?>