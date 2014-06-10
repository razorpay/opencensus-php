@extends('layout')

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
			<div class="content boxed" id="sidebar">
				<h2 class="lined">
					<div class="title">Features</div>
				</h2>
				<ul>
					<li class="active" data-tab="dashboard">
						<a href="./#!/">Dashboard</a>
					</li>
					<li data-tab="transactions">
						<a href="./#!/transactions">Transactions</a>
					</li>
					<li data-tab="refunds">
						<a href="./#!/refunds">Refunds</a>
					</li>
					<li data-tab="settlements">
						<a href="./#!/settlements">Settlements</a>
					</li>
				</ul>
				<h2 class="lined">
					<div class="title">Settings</div>
				</h2>
				<ul>
					<li data-tab="keys">
						<a href="./#!/keys">API Keys</a>
					</li>
					<li data-tab="activation">
						<a href="./#!/activation">Activation</a>
					</li>
					<li data-tab="account">
						<a href="./#!/account">Account</a>
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
							<div class="data-item-value" id="last-transfer-stat">₹2,449</div>
							<div class="data-item-desc">Last Transfer</div>
						</div>
						<div class="data-item col-1-4">
							<div class="data-item-value" id="total-txn-stat"></div>
							<div class="data-item-desc">Total Transactions</div>
						</div>
						<div class="data-item col-1-4">
							<div class="data-item-value" id="success-stat"></div>
							<div class="data-item-desc">Success Rate</div>
						</div>
						<div class="data-item col-1-4">
							<div class="data-item-value" id="total-amount-stat"></div>
							<div class="data-item-desc">Total Volume (INR)</div>
						</div>
					</div>
					<div id="transactions-line-chart-wrapper">
						<div id="controls-wrapper">
							<h2 class="col-1-3">Overview</h2>
							<div class="col-1-3 btn-group">
								<a class="btn active" data-interval="day" id="interval-day">Day</a>
								<a class="btn" data-interval="week" id="interval-week">Week</a>
								<a class="btn" data-interval="month" id="interval-month">Month</a>
								<a class="btn" data-interval="year" id="interval-year">Year</a>
							</div>
							<div class="col-1-3" id="datepicker-group">
								<input type="text" class="datepicker" id="date-start">-
								<input type="text" class="datepicker" id="date-end">
							</div>
						</div>
						<div id="transactions-line-chart" data-type="txn-volume-line">
							
						</div>
					</div>
					<div id="count-line-chart-wrapper">
						<div id="transaction-count-line-chart-half" class="col-1-2" data-type="txn-count-line">
							
						</div>
						<div id="customer-count-line-chart">
							
						</div>
					</div>
				</div>
				<div id="transactions" class="hidden panel">
					<div id="count-line-chart-wrapper">
						<div id="transaction-count-line-chart-full" data-type="txn-count-line">
							
						</div>
					</div>
					<div class="list-wrapper">
						<h2>Recent Transactions</h2>
						<ul class="transaction-list" id="transaction-list-all" data-type="txn-list">
							
						</ul>
					</div>
				</div>
				<div id="transaction-one" class="hidden panel">
					<div id="transaction-details">
						<h2>Details</h2>
						<div class="details grid"></div>
					</div>
				</div>
				<div id="refunds" class="hidden panel">
					<div class="list-wrapper">
						<h2>Recent Refunds</h2>
						<ul class="transaction-list" id="refund-list" data-type="txn-list">
							
						</ul>
					</div>
				</div>
				<div id="settlements" class="hidden panel">
					<div class="list-wrapper">
						<h2>Recent Settlements</h2>
						<ul class="transaction-list" id="settle-list" data-type="txn-list">
							
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
@stop