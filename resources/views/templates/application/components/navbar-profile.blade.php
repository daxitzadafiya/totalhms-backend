<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="/vendor/wrappixel/monster-admin/4.2.1/assets/images/users/1.jpg" alt="user" class="profile-pic" /></a>
    <div class="dropdown-menu dropdown-menu-right animated flipInY">
        <ul class="dropdown-user">
            <li>
                <div class="dw-user-box">
                    <div class="u-img"><img src="/vendor/wrappixel/monster-admin/4.2.1/assets/images/users/1.jpg" alt="user"></div>
                    <div class="u-text">
                        <h4>Steave Jobs</h4>
                        <p class="text-muted">{{ \Auth::user()->email }}</p><a href="{{ url('user/profile') }}" class="btn btn-rounded btn-danger btn-sm">View Profile</a></div>
                </div>
            </li>
            <li role="separator" class="divider"></li>
            <li><a href="{{ url('user/profile') }}"><i class="ti-user"></i> My Profile</a></li>
           {{--<li><a href="#"><i class="ti-wallet"></i> My Balance</a></li>
            <li><a href="#"><i class="ti-email"></i> Inbox</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="#"><i class="ti-settings"></i> Account Setting</a></li>--}}
            <li role="separator" class="divider"></li>
            <li><a href="/logout"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>
</li>
