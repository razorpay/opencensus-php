@extends('admin.layoutGenerated')

@section('panelcontent')
	<h2>Pending Activations</h2>
    <table class = "table table-hover">
    <thead>
        <th>Merchant ID</th>
        <th>Merchant Name</th>
        <th>Merchant Email</th>
        <th>Registration Date</th>
        <th>Last Update</th>
        <th>Actions</th>
    </thead>
    <tbody>
    @foreach($activations as $merchantDetails)
        <tr>
            <td>{{{$merchantDetails['merchant']['id']}}}</td>
            <td>{{{$merchantDetails['merchant']['name']}}}</td>
            <td>{{{$merchantDetails['merchant']['email']}}}</td>
            <td>{{{$merchantDetails['created_at']}}}</td>
            <td>{{{$merchantDetails['updated_at']}}}</td>
            <td>
                <a target="_blank" href="/admin/merchant/{{{$merchantDetails['merchant']['id']}}}/login?_token={{{csrf_token()}}}"><button>Login as Merchant</button></a>
                <a href = "/admin/merchant/{{{$merchantDetails['merchant']['id']}}}"><button>Manage Merchant</button></a>
            </td>
        </tr>
    @endforeach
    </tbody>
    </table>
@stop