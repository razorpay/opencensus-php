@extends('admin.layoutGenerated')

@section('panelcontent')

@if (Session::has('success'))
    @if (Session::get('success') === false)
        <div class="alert alert-dismissable alert-danger">
                There was an error in deleting the admin
        </div>
    @else
        <div class="alert alert-dismissable alert-success">Admin Deleted! </div>
    @endif
@endif
<table class="table table-hover">
    <thead>
        <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email Id</th>
            <th>Created At</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @foreach($admins as $admin)
        <tr>
            <td>{{{$admin['name']}}}</td>
            <td>{{{$admin['username']}}}</td>
            <td>{{{$admin['email']}}}</td>
            <td>{{{$admin['created_at']}}}</td>
            <td>
            @if ($admin['superadmin']==1)
            Super Admin
            @else
            Standard Admin
            @endif
            </td>
            <td>
                @if ($admin['id'] != Auth::admin()->get()->id)
                <form action="/admin/users/{{{$admin['id']}}}/delete">
                <input type="hidden" name="_token" value="{{{csrf_token()}}}">
                <a href="" style="color: #ff0000;" onclick="if (confirm('Are you sure you want to delete this admin?')){parentNode.submit();} return false;">
                Delete
                </a>
                </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<a href="/admin/users/add">Add Admin</a>
@stop