<div class="user-profile">
    <!-- User profile image -->
    <div class="profile-img"> <img src="/vendor/wrappixel/monster-admin/4.2.1/assets/images/users/1.jpg" alt="user" /> </div>
    <!-- User profile text-->
    <div class="profile-text"> <a href="#" class="dropdown-toggle link u-dropdown" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true"> {{ ucfirst(\Auth::user()->name) }} <span class="caret"></span></a>
        <div class="dropdown-menu animated flipInY">
            <a href="{{ url('user/profile') }}" class="dropdown-item"><i class="ti-user"></i> My Profile</a>
            {{--<a href="#" class="dropdown-item"><i class="ti-wallet"></i> My Balance</a>
            <a href="#" class="dropdown-item"><i class="ti-email"></i> Inbox</a>
            <div class="dropdown-divider"></div> <a href="{{ url('user/profile') }}" class="dropdown-item"><i class="ti-settings"></i> Account Setting</a>
            --}} <div class="dropdown-divider"></div> <a href="/logout" class="dropdown-item"><i class="fa fa-power-off"></i> Logout</a>
        </div>
    </div>
</div>
