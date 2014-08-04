@extends('admin.layoutGenerated')

@section('panelcontent')
    <table class = "table table-hover">
    <thead>
        <th>Plan ID</th>
        <th>Plan Name</th>
        <th>No. of Rules</th>
        <th>Actions</th>
    </thead>
    <tbody>
    @foreach($plans as $plan)
        <tr>
            <td>{{{$plan['id']}}}</td>
            <td>{{{$plan['name']}}}</td>
            <td>{{{$plan['count']}}}</td>
            <td>
                <a href="/admin/pricing/{{{$plan['id']}}}"><button>View/Edit Plan Rules</button></a>
            </td>
        </tr>
    @endforeach
    </tbody>
    </table>
    <a href="/admin/pricing/new">Create new plan</a>
@stop