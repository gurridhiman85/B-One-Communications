@php
    $segment = Request::segment(1);
    $segment2 = Request::segment(2);
@endphp
<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                @if(\App\Helpers\Helper::CheckPermission(null,'organization','view') || Auth::user()->IsAdmin)
                    <li class="{!! ($segment == "organization" || $segment == "organizations") ? 'active' : '' !!}">
                        <a
                                class="waves-effect waves-dark"
                                href="{!! URL::to('/organizations') !!}"
                                aria-expanded="false"
                        >
                            <i class="ti-layout-media-center-alt"></i>
                            <span class="hide-menu">
                                Organizations
                            </span>
                        </a>
                    </li>
                @endif

                @if(\App\Helpers\Helper::CheckPermission('settings','users','view') || Auth::user()->IsAdmin)
                    <li class="{!! ($segment == "users" || $segment == "users") ? 'active' : '' !!}">
                        <a
                                class="waves-effect waves-dark"
                                href="{!! URL::to('/users') !!}"
                                aria-expanded="false"
                        >
                            <i class="ti-user"></i>
                            <span class="hide-menu">
                                Users
                            </span>
                        </a>
                    </li>
                    <hr style="border-top: 3px solid rgba(0, 0, 0, .1)">
                @endif

                @if(\App\Helpers\Helper::CheckPermission(null,'department','view') || Auth::user()->IsAdmin)
                    <li class="{!! ($segment == "department" || $segment == "departments") ? 'active' : '' !!}">
                        <a
                                class="waves-effect waves-dark"
                                href="{!! URL::to('/departments') !!}"
                                aria-expanded="false"
                        >
                            <i class="ti-layout-grid2"></i>
                            <span class="hide-menu">
                                Departments
                            </span>
                        </a>
                    </li>
                @endif

                @if(\App\Helpers\Helper::CheckPermission(null,'extension','view') || Auth::user()->IsAdmin)
                    <li class="{!! ($segment == "extension" || $segment == "extensions") ? 'active' : '' !!}">
                        <a
                                class="waves-effect waves-dark"
                                href="{!! URL::to('/extensions') !!}"
                                aria-expanded="false"
                        >
                            <i class="ti-id-badge"></i>
                            <span class="hide-menu">
                                Extensions
                            </span>
                        </a>

                    </li>
                @endif

                @if(\App\Helpers\Helper::CheckPermission(null,'phonenumber','view') || Auth::user()->IsAdmin)
                    <li class="{!! ($segment == "phonenumber" || $segment == "phonenumbers") ? 'active' : '' !!}">
                        <a
                                class="waves-effect waves-dark"
                                href="{!! URL::to('/phonenumbers') !!}"
                                aria-expanded="false"
                        >
                            <i class="ti-mobile"></i>
                            <span class="hide-menu">
                                Phone Numbers
                            </span>
                        </a>

                    </li>
                @endif

                @if(\App\Helpers\Helper::CheckPermission(null,'announcement','view') || Auth::user()->IsAdmin)
                    <li class="{!! ($segment == "announcement" || $segment == "announcement") ? 'active' : '' !!}">
                        <a
                                class="waves-effect waves-dark"
                                href="{!! URL::to('/announcement') !!}"
                                aria-expanded="false"
                        >
                            <i class="ti-announcement"></i>
                            <span class="hide-menu">
                                Announcements
                            </span>
                        </a>

                    </li>
                @endif
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>
