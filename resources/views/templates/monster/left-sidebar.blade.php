<!-- ============================================================== -->
<!-- Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->
<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- User profile -->
        @include('templates.application.components.sidebar-profile')
        <!-- End User profile text-->
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                @if(Auth::user()->user_role == 1)

                <li>
                    <a class="has-arrow" href="JavaScript:void(0);" aria-expanded="false">
                        <i class="mdi mdi-home"></i>
                        <span class="hide-menu">Admin User</span>
                    </a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="{{ url('/admin/user/list') }}">User List</a></li>
                    </ul>
                </li>
                @endif

                <li>
                    <a class="has-arrow" href="JavaScript:void(0);" aria-expanded="false">
                        <i class="mdi mdi-hospital-building"></i>
                        <span class="hide-menu">Company</span>
                    </a>
                    <ul aria-expanded="false" class="collapse">

                        @if(Auth::user()->user_role == 1)

                        <li><a href="{{ url('/admin/company/list') }}">Company List</a></li>
                        <li><a href="{{ url('/admin/company/request/list') }}">Company Request List</a></li>
                        @endif

                        @if(Auth::user()->user_role == 2)
                        <li><a href="{{ url('/foretak/mittforetak') }}">Company Info</a></li>

                        <li><a class="waves-effect waves-dark" aria-expanded="true" href="{{ url('foretak/innstrukser') }}">Innstrukser</a></li>

                        <li><a href="{{ url('foretak/malsetting') }}">HSE Goals</a></li>
                        <li><a href="{{ url('foretak/rutiner') }}">Routines</a></li>
                        <li><a href="{{ url('foretak/kontakter') }}">Contacts</a></li>
                        <li><a href="{{ url('foretak/risikoområder') }}" >Risk areas</a></li>

                        @endif
                    </ul>
                </li>
                @if(Auth::user()->user_role == 2)
                     <!--task--->
                     <li>
                    <a class="has-arrow" href="JavaScript:void(0);" aria-expanded="false">
                        <i class="mdi mdi-arrange-send-to-back"></i>
                        <span class="hide-menu">Oppgaver </span>
                    </a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="{{ url('/kalender') }}">Kalender</a></li>
                        <li><a href="{{ url('/handlingsplan') }}">Handlingsplan</a></li>

                    </ul>
                </li>
                <li>
                    <a class="has-arrow" href="JavaScript:void(0);" aria-expanded="false">
                        <i class="far fa-id-badge"></i>
                        <span class="hide-menu">Employee</span>
                    </a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="{{ url('/ansatte/employees') }}">Employees </a></li>
                        <li><a class="waves-effect waves-dark" aria-expanded="true" href="{{ url('ansatte/organization') }}">Organisasjonskart</a></li>
                        <li><a href="{{ url('ansatte/malsetting') }}">Timeregistrering</a></li>
                        <li><a href="{{ url('ansatte/work-plan') }}">Arbeidsplan</a></li>
                        <li><a href="{{ url('ansatte/absence') }}">Fravær</a></li>
                        <li><a href="{{ url('ansatte/appraisals ') }}">Medarbeidersamtaler</a></li>

                    </ul>
                </li>
                <li>
                    <a class="" href="{{ url('/document/list') }}" aria-expanded="false">
                        <i class="mdi mdi-chemical-weapon"></i>
                        <span class="hide-menu">Dokumenter</span>
                    </a>
                </li>
                <li>
                    <a class="" href="{{ url('/avvik') }}" aria-expanded="false">
                        <i class="fas fa-boxes"></i>
                        <span class="hide-menu">Deviation</span>
                    </a>
                </li>
                <li>
                    <a class="has-arrow" href="JavaScript:void(0);" aria-expanded="false">
                        <i class="mdi mdi-kodi"></i>
                        <span class="hide-menu">Risk Areas</span>
                    </a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="{{ url('/risikoanalyse') }}">Risk analysis </a></li>
                        <li><a href="{{ url('sjekklister/oversikt') }}"> Sjekklister maler</a></li>
                        <li><a href="{{ url('kartlegging/oversikt') }}">Kartlegging</a></li>
                        <li><a href="{{ url('vernerunder/oversikt') }}">Vernerunder</a></li>
                    </ul>
                </li>
                <li>
                    <a href="{{ url('/project') }}" aria-expanded="false">
                        <i class="fas fa-database"></i>
                        <span class="hide-menu">Project</span>
                    </a>
                </li>
                @endif

            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
    <!-- Bottom points-->
    <div class="sidebar-footer">
        <!-- item--><a href="" class="link" data-toggle="tooltip" title="Settings"><i class="ti-settings"></i></a>
        <!-- item--><a href="" class="link" data-toggle="tooltip" title="Email"><i class="mdi mdi-gmail"></i></a>
        <!-- item--><a href="" class="link" data-toggle="tooltip" title="Logout"><i class="mdi mdi-power"></i></a>
    </div>
    <!-- End Bottom points-->
</aside>
<!-- ============================================================== -->
<!-- End Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->