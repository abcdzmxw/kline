<div class="main-menu">
    <div class="main-menu-content">
        <aside class="main-sidebar <?php echo e($configData['sidebar_style'], false); ?> shadow">
            <div class="navbar-header">
                <ul class="nav navbar-nav flex-row">
                    <li class="nav-item mr-auto">
                        <a href="<?php echo e(admin_url('/'), false); ?>" class="navbar-brand waves-effect waves-light">
                            <span class="logo-mini"><?php echo config('admin.logo-mini'); ?></span>
                            <span class="logo-lg"><?php echo config('admin.logo'); ?></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar pb-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" style="padding-top: 10px">
                    <?php echo admin_section(AdminSection::LEFT_SIDEBAR_MENU_TOP); ?>


                    <?php echo admin_section(AdminSection::LEFT_SIDEBAR_MENU); ?>


                    <?php echo admin_section(AdminSection::LEFT_SIDEBAR_MENU_BOTTOM); ?>

                </ul>
            </div>
        </aside>
    </div>
</div><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/partials/sidebar.blade.php ENDPATH**/ ?>