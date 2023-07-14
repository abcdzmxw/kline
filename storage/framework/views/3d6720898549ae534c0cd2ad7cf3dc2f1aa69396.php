<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale()), false); ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge">
    
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title><?php if(! empty($header)): ?><?php echo e($header, false); ?> | <?php endif; ?> <?php echo e(Dcat\Admin\Admin::title(), false); ?></title>

    <?php if(! config('admin.disable_no_referrer_meta')): ?>
        <meta name="referrer" content="no-referrer"/>
    <?php endif; ?>

    <?php if(! empty($favicon = Dcat\Admin\Admin::favicon())): ?>
        <link rel="shortcut icon" href="<?php echo e($favicon, false); ?>">
    <?php endif; ?>

    <?php echo admin_section(\AdminSection::HEAD); ?>


    <?php echo Dcat\Admin\Admin::asset()->headerJsToHtml(); ?>


    <?php echo Dcat\Admin\Admin::asset()->cssToHtml(); ?>

</head>

<body class="dcat-admin-body full-page <?php echo e($configData['body_class'], false); ?>">

<script>
    var Dcat = CreateDcat(<?php echo Dcat\Admin\Admin::jsVariables(); ?>);
</script>


<?php echo admin_section(\AdminSection::BODY_INNER_BEFORE); ?>


<div class="app-content content">
    <div class="wrapper" id="<?php echo e($pjaxContainerId, false); ?>">
        <?php echo $__env->yieldContent('app'); ?>
    </div>
</div>

<?php echo admin_section(\AdminSection::BODY_INNER_AFTER); ?>


<?php echo Dcat\Admin\Admin::asset()->jsToHtml(); ?>


<script>Dcat.boot();</script>

</body>
</html><?php /**PATH /www/wwwroot/server.arrcoin.net/vendor/dcat/laravel-admin/src/../resources/views/layouts/full-page.blade.php ENDPATH**/ ?>