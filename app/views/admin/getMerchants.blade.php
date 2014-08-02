@extends('admin.layoutGenerated')

@section('panelcontent')
    <table class = "table table-hover">
    <thead>
        <th>Merchant ID</th>
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
                <form target="_blank" action="/admin/merchant/{{{$merchant['id']}}}/login">
                <input type="hidden" name="_token" value="{{{csrf_token()}}}">
                <button type="submit">Login as Merchant</button>
                </form>
                <button type="submit">
                <a href = "/admin/merchant/{{{$merchant['id']}}}/details">Merchant Details</a>
                </button>
                <button type="submit">
                <a href = "/admin/merchant/{{{$merchant['id']}}}">Manage Merchant Status</a>
                </button>
            </td>
        </tr>
    @endforeach
    </tbody>
    </table>
@stop