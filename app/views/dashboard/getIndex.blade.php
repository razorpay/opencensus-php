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
					<div class="title">Features</div>
				</h2>
				<ul>
					<li class="active">
						<a href="./#!/">Dashboard</a>
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
			<div class="content boxed" id="dashboard-wrapper">
				<div id="dashboard">
					<div class="horizontal-data-wrapper">
						<div class="data-item col-1-4">
							<div class="data-item-value">₹2,449</div>
							<div class="data-item-desc">Last Transfer</div>
						</div>
						<div class="data-item col-1-4">
							<div class="data-item-value">813</div>
							<div class="data-item-desc">Total Transactions</div>
						</div>
						<div class="data-item col-1-4">
							<div class="data-item-value">97%</div>
							<div class="data-item-desc">Success Rate</div>
						</div>
						<div class="data-item col-1-4">
							<div class="data-item-value">₹1,25,000</div>
							<div class="data-item-desc">Total Volume (INR)</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@stop