<ul class="nav nav-tabs page-header-tabs">
    <li class="nav-item">
        <a class="nav-link {{Request::is('admin/agent/view*') || Request::is('admin/customer/view*') || Request::is('admin/merchant/view*')?'active':''}}"
           @if($user->type == 1)
               href="{{route('admin.agent.view',[$user['id']])}}"
           @elseif($user->type == 2)
               href="{{route('admin.customer.view',[$user['id']])}}"
           @elseif($user->type == 3)
               href="{{route('admin.merchant.view',[$user['id']])}}"
           @else
               href="#"
            @endif
        >{{translate('details')}}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{Request::is('admin/agent/transaction*') || Request::is('admin/customer/transaction*') || Request::is('admin/merchant/transaction*')?'active':''}}"           @if($user->type == 1)
               href="{{route('admin.agent.transaction',[$user['id']])}}"
           @elseif($user->type == 2)
               href="{{route('admin.customer.transaction',[$user['id']])}}"
           @elseif($user->type == 3)
               href="{{route('admin.merchant.transaction',[$user['id']])}}"
           @else
               href="#"
            @endif
        >{{translate('Transactions')}}</a>
    </li>
    @if(Request::is('admin/agent*') || Request::is('admin/customer*'))
        <li class="nav-item">
            <a class="nav-link {{Request::is('admin/agent/log*') || Request::is('admin/customer/log*')?'active':''}}"
               @if(isset($user) && $user->type == 1)
                   href="{{route('admin.agent.log',[$user['id']])}}"
               @elseif(isset($user) && $user->type == 2)
                   href="{{route('admin.customer.log',[$user['id']])}}"
               @else
                   href="#"
                @endif
            >{{translate('Logs')}}</a>
        </li>
    @endif

</ul>
