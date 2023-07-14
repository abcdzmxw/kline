<link rel="stylesheet" href="/static/css/login.css">
<div class="row login_main">
    <div class="col-md-3 col-sm-3 col-12 ">
        <div class="login-page">

            <div class="auth-brand text-center text-lg-left">
                <img src="/uploads/<?php echo get_setting_value('titles_logo','website'); ?>" width="35"> &nbsp;<?php echo get_setting_value('name','website'); ?>

            </div>

            <div class="login-box">
                <div class="login-logo mb-2">
                    <h4 class="mt-0">Sign In</h4>
                    <p class="login-box-msg mt-1 mb-1"><?php echo e(__('admin.welcome_back'), false); ?></p>
                </div>
                <div class="card">
                    <div class="card-body login-card-body">

                        <form id="login-form" method="POST" action="<?php echo e(admin_url('auth/login'), false); ?>">

                            <input type="hidden" name="_token" value="<?php echo e(csrf_token(), false); ?>"/>

                            <fieldset class="form-label-group form-group position-relative has-icon-left">
                                <input
                                        type="text"
                                        class="form-control <?php echo e($errors->has('username') ? 'is-invalid' : '', false); ?>"
                                        name="username"
                                        placeholder="<?php echo e(trans('admin.username'), false); ?>"
                                        value="<?php echo e(old('username'), false); ?>"
                                        required
                                        autofocus
                                >

                                <div class="form-control-position">
                                    <i class="feather icon-user"></i>
                                </div>

                                <label for="email"><?php echo e(trans('admin.username'), false); ?></label>

                                <div class="help-block with-errors"></div>
                                <?php if($errors->has('username')): ?>
                                    <span class="invalid-feedback text-danger" role="alert">
                                                    <?php $__currentLoopData = $errors->get('username'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="control-label" for="inputError"><i class="feather icon-x-circle"></i> <?php echo e($message, false); ?></span><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </span>
                                <?php endif; ?>
                            </fieldset>

                            <fieldset class="form-label-group form-group position-relative has-icon-left">
                                <input
                                        minlength="5"
                                        maxlength="20"
                                        id="password"
                                        type="password"
                                        class="form-control <?php echo e($errors->has('password') ? 'is-invalid' : '', false); ?>"
                                        name="password"
                                        placeholder="<?php echo e(trans('admin.password'), false); ?>"
                                        required
                                        autocomplete="current-password"
                                >

                                <div class="form-control-position">
                                    <i class="feather icon-lock"></i>
                                </div>
                                <label for="password"><?php echo e(trans('admin.password'), false); ?></label>

                                <div class="help-block with-errors"></div>
                                <?php if($errors->has('password')): ?>
                                    <span class="invalid-feedback text-danger" role="alert">
                                                    <?php $__currentLoopData = $errors->get('password'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="control-label" for="inputError"><i class="feather icon-x-circle"></i> <?php echo e($message, false); ?></span><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </span>
                                <?php endif; ?>

                            </fieldset>
                            <div class="form-group d-flex justify-content-between align-items-center">
                                <div class="text-left">
                                    <fieldset class="checkbox">
                                        <div class="vs-checkbox-con vs-checkbox-primary">
                                            <input id="remember" name="remember"  value="1" type="checkbox" <?php echo e(old('remember') ? 'checked' : '', false); ?>>
                                            <span class="vs-checkbox">
                                                                <span class="vs-checkbox--check">
                                                                  <i class="vs-icon feather icon-check"></i>
                                                                </span>
                                                            </span>
                                            <span> <?php echo e(trans('admin.remember_me'), false); ?></span>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary float-right login-btn">

                                <?php echo e(__('admin.login'), false); ?>

                                &nbsp;
                                <i class="feather icon-arrow-right"></i>
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>




    </div>
    <div class="col-md-9 col-sm-9 col-12 auth-fluid-right">
            <div class="auth-user-testimonial">
                <h2 class="mb-3">Stay Hungry, Stay Foolish!</h2>
                <p class="lead"><i class="mdi mdi-format-quote-open"></i>你如果出色地完成了某件事，那你应该再做一些其他的精彩事儿。不要在前一件事上徘徊太久，想想接下来该做什么... <i class="mdi mdi-format-quote-close"></i>
                </p>
                <p>
                    - 乔布斯
                </p>
            </div> <!-- end auth-user-testimonial-->
    </div>
</div>

<script>
Dcat.ready(function () {
    // ajax表单提交
    $('#login-form').form({
        validate: true,
        success: function (data) {
            if (! data.status) {
                Dcat.error(data.message);

                return false;
            }

            Dcat.success(data.message);

            location.href = data.redirect;

            return false;
        }
    });
});
</script>
<?php /**PATH /www/wwwroot/server.arrcoin.net/resources/views/admin/login/login.blade.php ENDPATH**/ ?>