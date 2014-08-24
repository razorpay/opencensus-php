@extends('layoutGenerated')

@section('content')
	<div class="grid grid-pad">
		<div class="col-1-4 spaced-right-box">
			<!-- <div class="content boxed spaced-bottom-box" id="livemode">
				<div class="button-desc active">Test</div>
				<div class="button-wrap">
					<div class="button-bg">
						<div class="button-switch"></div>
					</div>
				</div>
				<div class="button-desc">Live</div>
			</div> -->
			@include('sidebar')
		</div>
		<div class="col-9-12 spaced-right-box">
			<div class="content boxed" id="dashboard-wrapper">
				<div class="centered form-wrapper">
		            <div class="content">
		                <h2>Change Password</h2>
		                {{ Form::open(array('action' => array('MerchantController@postPassword'))) }}
		                    <div class="text">
		                        <input type="password" placeholder="Old Password" name="old_password">	                        
		                        <input type="password" placeholder="New Password" name="password">
		                        <input type="password" placeholder="Confirm Password" name="password_confirmation">
		                    </div>
		                    <button id="form-button">Update</button>
		                {{ Form::close() }}
		                @if (Session::has('error'))
		                    <ul class="error-message">
		                    @foreach(Session::get('error') as $message)
		                        <li>{{ $message }}</li>
		                    @endforeach
		                    </ul>
		                @endif
		            </div>
			    </div>
			</div>
		</div>
	</div>
@stop
