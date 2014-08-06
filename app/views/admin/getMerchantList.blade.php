@extends('admin.layoutGenerated')

@section('panelcontent')
    <table class = "table table-hover">
    <thead>
        <th>Merchant ID</th>
        <th>Merchant Name</th>
        <th>Merchant Email</th>
        <th>Created At</th>
        <th>Confirmed?</th>
        <th>Activated?</th>
        <th>Actions</th>
    </thead>
    <tbody>
    @foreach($data as $merchant)
        <tr>
            <td>{{{$merchant['id']}}}</td>
            <td>{{{$merchant['name']}}}</td>
            <td>{{{$merchant['email']}}}</td>
            <td>{{{$merchant['created_at']}}}</td>
            <td>
            @if($merchant['confirm_token'] == null)
            Yes
            @else
            No
            @endif
            </td>
            <td>
            @if($merchant['live'])
            Yes
            @else
            No
            @endif
            </td>
            <td>
                <a target="_blank" href="/admin/merchant/{{{$merchant['id']}}}/login?_token={{{csrf_token()}}}"><button>Login as Merchant</button></a>
                <a href = "/admin/merchant/{{{$merchant['id']}}}"><button>Manage Merchant</button></a>
            </td>
        </tr>
    @endforeach
    </tbody>
    </table>
@stop