@extends('layout')

@section('content')
	<div class="grid grid-pad">
		<div class="col-1-4 spaced-right-box">
			<div class="content boxed spaced-bottom-box" id="livemode">
				<div class="button-desc active-desc">Test</div>
				<div class="button-wrap">
					<div class="button-bg">
						<div class="button-switch"></div>
					</div>
				</div>
				<div class="button-desc">Live</div>
			</div>
			<div class="content boxed" id="sidebar">
				<h2 class="lined">
					<div class="title">Dashboard</div>
				</h2>
				<ul>
					<li class="active">
						<a href="./#!/">Getting Started</a>
					</li>
					<li>
						<a href="./#!/payments">Payments</a>
					</li>
					<li>
						<a href="./#!/customers">Customers</a>
					</li>
					<li>
						<a href="./#!/transfers">Transfers</a>
					</li>
					<li>
						<a href="./#!/recipients">Recipients</a>
					</li>
					<li>
						<a href="./#!/plans">Plans</a>
					</li>
					<li>
						<a href="./#!/logs">Logs</a>
					</li>
				</ul>
			</div>
		</div>
		<div class="col-9-12 spaced-right-box">
			<div class="content boxed" id="dashboard"></div>
		</div>
	</div>
@stop