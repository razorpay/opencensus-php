<div class="content boxed" id="sidebar">
    <h3 class="lined">
        <div class="title">Admin Panel</div>
    </h3>
    <ul>
        <li>
            <a href="/admin/">Dashboard</a>
        </li>
        <li>
            <a href="/admin/merchant/list">Merchants</a>
        </li>
        <li>
            <a href="/admin/pricing/list">Pricing Plans</a>
        </li>
        @if(Auth::admin()->user()->isSuperAdmin())
        <li>
            <a href="/admin/users">Manage Admins</a>
        </li>
        @endif
        <li>
            <a href="/admin/password">Change Password</a>
        </li>
    </ul>
</div>