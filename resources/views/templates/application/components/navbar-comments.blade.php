@inject('notifications', 'navbar.notifications')

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle text-muted text-muted waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="mdi mdi-message"></i>
        @if($notifications->get()->count() > 0)
        <div class="notify">
            <span class="heartbit"></span> <span class="point"></span>
        </div>
        @endif
    </a>
    <div class="dropdown-menu mailbox animated bounceInDown">
        <ul>
            <li>
                <div class="drop-title">Notifications ({{ $notifications->get()->count() }})</div>
            </li>
            <li>
                <div class="message-center">
                    @foreach($notifications->get()->slice($start = 0, $howMany = 5) as $notification)

                    <!-- Message -->
                    <a href="#">
                        <div class="btn btn-danger btn-circle"><i class="fa fa-link"></i></div>
                        <div class="mail-contnet">
                            <h5>{{ $notification->title }}</h5>
                            <span class="mail-desc">{{ str_limit($notification->body, 40) }}</span>
                            <span class="time">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                    </a>

                    @endforeach

                    <!-- Message -->
                    <a href="#">
                        <div class="btn btn-success btn-circle"><i class="ti-calendar"></i></div>
                        <div class="mail-contnet">
                            <h5>Event today</h5> <span class="mail-desc">Just a reminder that you have event</span> <span class="time">9:10 AM</span> </div>
                    </a>
                    <!-- Message -->
                    <a href="#">
                        <div class="btn btn-info btn-circle"><i class="ti-settings"></i></div>
                        <div class="mail-contnet">
                            <h5>Settings</h5> <span class="mail-desc">You can customize this template as you want</span> <span class="time">9:08 AM</span> </div>
                    </a>
                    <!-- Message -->
                    <a href="#">
                        <div class="btn btn-primary btn-circle"><i class="ti-user"></i></div>
                        <div class="mail-contnet">
                            <h5>Pavan kumar</h5> <span class="mail-desc">Just see the my admin!</span> <span class="time">9:02 AM</span> </div>
                    </a>
                </div>
            </li>
            <li>
                <a class="nav-link text-center" href="javascript:void(0);"> <strong>Check all notifications</strong> <i class="fa fa-angle-right"></i> </a>
            </li>
        </ul>
    </div>
</li>

