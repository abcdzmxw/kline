<style>
    .dashboard-title .links {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    .dashboard-title .links > a {
        padding: 0 25px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .1rem;
        text-decoration: none;
        text-transform: uppercase;
    }
    .dashboard-title h1 {
        font-weight: 200;
        font-size: 2.5rem;
    }
    .dashboard-title .avatar {
        background: #fff;
        border: 2px solid #fff;
        width: 70px;
        height: 70px;
    }
</style>

<div class="dashboard-title card">
    <div class="card-body">
        <div class="text-center ">
            <img class="avatar img-circle shadow mt-1" src="{{ admin_asset('@admin/images/logo.png') }}">

            <div class="text-center mb-1">
                <h1 class="mb-3 mt-2 text-primary-darker">SHENBTS</h1>
                <div class="links">
                    <a href="#" target="_blank">Github</a>
                    <a href="#" id="doc-link" target="_blank">{{ __('admin.documentation') }}</a>
                    <a href="#" id="demo-link" target="_blank">{{ __('admin.extensions') }}</a>
                    <a href="#" id="demo-link" target="_blank">{{ __('admin.demo') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
