@extends('layouts.main')

@section('sidebar')
<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{route("dashboard")}}">
                <img src="{{asset("assets/images/logo.png")}}" alt="logo" class="img-fluid" style="height: 41px">
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{route("dashboard")}}"><img src="{{asset("assets/images/logo.png")}}" alt="logo" class="img-fluid" style="width: 52px;min-height: 30px;"></a>
        </div>
        <ul class="sidebar-menu">
            <li class="{{ request()->is('dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('dashboard')}}">
                    <i class="fas fa-fire"></i><span>Dashboard</span>
                </a>
            </li>
            @if(Session::has('user_access_permission'))
            @php ($role_permission = Session::get('user_access_permission'))

            @if(!empty($role_permission['admins']) || !empty($role_permission['user-roles']))
            <li style="display: none" class="dropdown 
                {{ 
                    request()->is('admins') || 
                    request()->is('admins/*') || 
                    request()->is('user-roles') || 
                    request()->is('user-roles/*') 
                    ? 'active' : '' 
                }}">
                <a href="#" class="nav-link has-dropdown">
                    <i class="fas fa-user"></i><span>Admins</span>
                </a>
                <ul class="dropdown-menu">
                    @if(!empty($role_permission['admins']))
                    <li class="{{ request()->is('admins') || request()->is('admins/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{route("admins.index")}}">List</a>
                    </li>
                    @endif
                    @if(!empty($role_permission['user-roles']))
                    <li class="{{ request()->is('user-roles') || request()->is('user-roles/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{route("user-roles.index")}}">Roles</a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif

            @if(!empty($role_permission['users']))
            <li class="{{ request()->is('users') || request()->is('users/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('users.index')}}">
                    <i class="fas fa-users"></i><span>Users</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['pet_breeds']))
            <li class="{{ request()->is('pet_breeds') || request()->is('pet_breeds/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('pet_breeds.index')}}">
                    <i class="fas fa-cubes"></i><span>Breeds</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['pet_types']))
            <li class="{{ request()->is('pet_types') || request()->is('pet_types/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('pet_types.index')}}">
                    <i class="fas fa-snowflake"></i><span>Pet Types</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['pets']))
            <li class="{{ request()->is('pets') || request()->is('pets/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('pets.index')}}">
                    <i class="fas fa-paw"></i><span>Pets</span>
                </a>
            </li>
            @endif
            @if(!empty($role_permission['groups']))
            <li class="{{ request()->is('groups') || request()->is('groups/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('groups.index')}}">
                    <i class="fas fa-users"></i><span>Groups</span>
                </a>
            </li>
            @endif
            @if(!empty($role_permission['events']))
            <li class="{{ request()->is('events') || request()->is('events/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('events.index')}}">
                    <i class="fas fa-calendar"></i><span>Events</span>
                </a>
            </li>
            @endif
            @if(!empty($role_permission['posts']))
            <li class="{{ request()->is('posts') || request()->is('posts/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('posts.index')}}">
                    <i class="fas fa-comment"></i><span>Posts</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['categories']))
            <li style="display: none" class="{{ request()->is('categories') || request()->is('categories/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('categories.index')}}">
                    <i class="fas fa-outdent"></i><span>Categories</span>
                </a>
            </li>
            @endif


            @if(!empty($role_permission['countries']))
            <li class="{{ request()->is('countries') || request()->is('countries/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('countries.index')}}">
                    <i class="fas fa-map"></i><span>Countries</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['cities']))
            <li class="{{ request()->is('cities') || request()->is('cities/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('cities.index')}}">
                    <i class="fas fa-map-marker"></i><span>Cities</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['aboutus']))
            <li style="display: none" class="{{ request()->is('aboutus') || request()->is('aboutus/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('aboutus.index')}}">
                    <i class="fas fa-address-card"></i><span>About us</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['contactus']))
            <li style="display: none" class="{{ request()->is('contactus') || request()->is('contactus/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('contactus.index')}}">
                    <i class="fas fa-phone"></i><span>Contact us</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['feedback']))
            <li style="display: none" class="{{ request()->is('feedback') || request()->is('feedback/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('feedback.index')}}">
                    <i class="fas fa-comment"></i><span>Feedback</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['faqs']))
            <li style="display: none" class="{{ request()->is('faqs') || request()->is('faqs/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('faqs.index')}}">
                    <i class="fas fa-question-circle"></i><span>Help & Faqs</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['terms']))
            <li style="display: none" class="{{ request()->is('terms') || request()->is('terms/*') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('terms.index')}}">
                    <i class="fas fa-bookmark"></i><span>Terms & Conditions</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['pushnotification']))
            <li style="display: none" class="{{ request()->is('pushnotification') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('pushnotification.index')}}">
                    <i class="fas fa-bullhorn"></i><span>Global Push Notification</span>
                </a>
            </li>
            @endif

            @if(!empty($role_permission['settings']))
            <li style="display: none" class="{{ request()->is('settings') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('settings.index')}}">
                    <i class="fas fa-cog"></i><span>Settings</span>
                </a>
            </li>
            @endif

            @endif
        </ul>
    </aside>
</div>
@endsection
