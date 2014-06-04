@extends('layout')

@section('content')
	<div class="grid grid-pad">
		<div class="col-1-4 spaced-right-box">
			<div class="content boxed spaced-bottom-box" id="livemode">
				<div class="button-desc active">Test</div>
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
					<li class="active" data-tab="dashboard">
						<a href="./#!/">Dashboard</a>
					</li>
					<li data-tab="payments">
						<a href="./#!/payments">Payments</a>
					</li>
					<li data-tab="customers">
						<a href="./#!/customers">Customers</a>
					</li>
					<li data-tab="transfers">
						<a href="./#!/transfers">Transfers</a>
					</li>
					<li data-tab="recipients">
						<a href="./#!/recipients">Recipients</a>
					</li>
					<li data-tab="plans">
						<a href="./#!/plans">Plans</a>
					</li>
					<li data-tab="logs">
						<a href="./#!/logs">Logs</a>
					</li>
				</ul>
			</div>
		</div>
		<div class="col-9-12 spaced-right-box">
			<div class="content boxed" id="dashboard-wrapper">
				<div id="loader" class="spinner">
					<div class="rect1"></div>
					<div class="rect2"></div>
					<div class="rect3"></div>
					<div class="rect4"></div>
					<div class="rect5"></div>
				</div>
				<div id="dashboard" class="hidden panel">
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
					<div id="transactions-line-chart-wrapper">
						<div id="controls-wrapper">
							<h2 class="col-1-3">Overview</h2>
							<div class="col-1-3 btn-group">
								<a class="btn active" id="interval-day">Day</a>
								<a class="btn" id="interval-week">Week</a>
								<a class="btn" id="interval-month">Month</a>
								<a class="btn" id="interval-year">Year</a>
							</div>
							<div class="col-1-3" id="datepicker-group">
								<input type="text" class="datepicker" id="date-start">-
								<input type="text" class="datepicker" id="date-end">
							</div>
						</div>
						<div id="transactions-line-chart">
							
						</div>
					</div>
					<div id="count-line-chart-wrapper">
						<div id="transaction-count-line-chart" class="col-1-2">
							
						</div>
						<div id="customer-count-line-chart">
							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@stop