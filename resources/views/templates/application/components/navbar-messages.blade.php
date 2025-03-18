@inject('messages', 'navbar.messages')

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="" id="2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="mdi mdi-email"></i>
        @if($messages->get()->count() > 0)
        <div class="notify">
            <span class="heartbit"></span> <span class="point"></span>
        </div>
        @endif
    </a>
    <div class="dropdown-menu mailbox animated bounceInDown" aria-labelledby="2">
        <ul>
            <li>
                <div class="drop-title">You have {{ $messages->get()->count() }} new messages</div>
            </li>
            <li>
                <div class="message-center">
                    @foreach($messages->get()->slice($start = 0, $howMany = 6) as $message)
                    <!-- Message -->
                    <a href="#">
                        <div class="user-img">
                            {{--Replace with User image--}}
                            <img src="/vendor/wrappixel/monster-admin/4.2.1/assets/images/users/1.jpg"
                                 alt="user"
                                 class="img-circle">
                            <span class="profile-status online float-right"></span> </div>
                        <div class="mail-contnet">
                            <h5>{{ $message->author->name }}</h5>
                            <span class="mail-desc"> {{ str_limit($message->title, 40) }}</span>
                            <span class="time">{{ $message->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </li>
            <li>
                <a class="nav-link text-center" href="javascript:void(0);">
                    @if($messages->get()->count() > 0)
                    <span> Displaying {{ $howMany }}. </span>
                    @endif
                    <strong> See all e-Mails</strong>
                    <i class="fa fa-angle-right"></i>
                </a>
            </li>
        </ul>
    </div>
</li>
