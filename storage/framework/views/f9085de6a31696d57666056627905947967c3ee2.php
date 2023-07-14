<body
    class="dcat-admin-body sidebar-mini layout-fixed <?php echo e($configData['body_class'], false); ?> <?php echo e($configData['sidebar_class'], false); ?>

    <?php echo e($configData['navbar_class'] === 'fixed-top' ? 'navbar-fixed-top' : '', false); ?> " >

    <script>
        var Dcat = CreateDcat(<?php echo Dcat\Admin\Admin::jsVariables(); ?>);
    </script>

    <?php echo admin_section(\AdminSection::BODY_INNER_BEFORE); ?>


    <div class="wrapper">
        <?php echo $__env->make('admin::partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <?php echo $__env->make('admin::partials.navbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="app-content content">
            <div class="content-wrapper" id="<?php echo e($pjaxContainerId, false); ?>" style="top: 0;min-height: 900px;">
                <?php echo $__env->yieldContent('app'); ?>
            </div>
        </div>
    </div>

    <footer class="main-footer pt-1">
        <p class="clearfix blue-grey lighten-2 mb-0 text-center">
            <span class="text-center d-block d-md-inline-block mt-25">
                Powered by
                <a target="_blank" href="https://github.com/jqhph/dcat-admin">Dcat Admin</a>
                <span>&nbsp;·&nbsp;</span>
                v<?php echo e(Dcat\Admin\Admin::VERSION, false); ?>

            </span>

            <button class="btn btn-primary btn-icon scroll-top pull-right" style="position: fixed;bottom: 2%; right: 10px;display: none">
                <i class="feather icon-arrow-up"></i>
            </button>
        </p>
    </footer>

    <?php echo admin_section(\AdminSection::BODY_INNER_AFTER); ?>


    <?php echo Dcat\Admin\Admin::asset()->jsToHtml(); ?>


    <script>Dcat.boot();</script>

</body>

</html><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/layouts/vertical.blade.php ENDPATH**/ ?>