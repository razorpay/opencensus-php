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
                <button type="submit">
                <a href = "/admin/pricing/{{{$plan['id']}}}">View/Edit Plan Rules</a>
                </button>
            </td>
        </tr>
    @endforeach
    </tbody>
    </table>
@stop