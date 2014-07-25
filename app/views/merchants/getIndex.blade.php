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
			@include('sidebar')
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
				<div id="account" class="hidden panel">
					<div class="account-details grid">
						<div class="col-1-3">Name</div>
						<div class="col-2-3" id="account-name"></div>
						<div class="col-1-3">Email</div>
						<div class="col-2-3" id="account-email"></div>
					</div>
				</div>
				<div id="keys" class="hidden panel">
					
				</div>
				<div id="activation" class="hidden panel">
				 <div class="centered activation-form-wrapper">
				 		<ol class="progtrckr">
							<li class="progtrckr-todo">Contact Details</li><!-- This comment is hack to avoid progress bar from becoming
	discontinuos		--><li class="progtrckr-todo">Bussiness Details</li><!--
						--><li class="progtrckr-todo">Promoters Details</li><!--
						--><li class="progtrckr-todo">Bank Details</li><!--
						--><li class="progtrckr-todo">Document Scans</li><!--
						--><li class="progtrckr-todo">Activate</li>
						</ol>
				 	<form class="form-horizontal" role="form" id="activation-form">
					 	<fieldset class="step">
							<legend>Contact Details</legend>
							<div class="form-group">
								<label for="contact_name" class="col-sm-4 control-label">Contact Name</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="contact_name" placeholder="Contact Name" required>
								</div>
							</div>
							<div class="form-group">
								<label for="contact_email" class="col-sm-4 control-label">Email</label>
								<div class="col-sm-8">
								  <input type="email" class="form-control" name="contact_email" placeholder="Email" required>
								</div>
							</div>
							<div class="form-group">
								<label for="contact_mobile" class="col-sm-4 control-label">Mobile</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="contact_mobile" placeholder="Mobile" required>
								</div>
							</div>
							<div class="form-group">
								<label for="contact_landline" class="col-sm-4 control-label">Landline</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="contact_landline" placeholder="Landline">
								</div>
							</div>
						</fieldset>
						<fieldset class="step">
							<legend>Bussiness Details</legend>
							<div class="form-group">
								<label for="bussiness_type" class="col-sm-4 control-label">Organisation Type</label>
								<div class="col-sm-8">
								  	<select name="bussiness_type" class="form-control" required>
			                        	<option value="1">Proprietership</option>
			                        	<option value="2">Individual</option>
			                        	<option value="3">Partnership</option>
			                        	<option value="4">Private Limited</option>
			                        	<option value="5">Public Limited</option>
			                        	<option value="6">LLP</option>
			                        	<option value="7">NGO</option>
			                        	<option value="8">Educational Institutes</option>
			                        	<option value="9">Trust</option>
			                        	<option value="10">Society</option>
			                        </select>
								</div>
							</div>
							<div class="form-group">
								<label for="bussines_category" class="col-sm-4 control-label">Bussiness Category</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussines_category" placeholder="Bussiness Category" required>
								</div>
							</div>
							<div class="form-group">
								<label for="bussines_subcategory" class="col-sm-4 control-label">Bussiness Subcategory</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussines_subcategory" placeholder="Bussiness Subcategory" required>
								</div>
							</div>
							<div class="form-group">
							<label for="bussiness_registered_address" class="col-sm-4 control-label">Registered Address</label>
								<div class="col-sm-8">
								  <textarea class="form-control" name="bussiness_registered_address" placeholder="Registered Address" required></textarea>
								</div>
							</div>
							<div class="form-group">
							<label for="bussiness_registered_state" class="col-sm-4 control-label">Registration State</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussiness_registered_state" placeholder="Registered State" required>
								</div>
							</div>
							<div class="form-group">
							<label for="bussiness_registered_city" class="col-sm-4 control-label">Registered City</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussiness_registered_city" placeholder="Registered City" required>
								</div>
							</div>
							<div class="form-group">
							<label for="bussiness_registered_pin" class="col-sm-4 control-label">Registered PIN</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussiness_registered_pin" placeholder="Registered PIN" required>
								</div>
							</div>

							<div class="form-group">
							<label for="bussiness_operation_address" class="col-sm-4 control-label">Operation Address</label>
								<div class="col-sm-8">
								  <textarea class="form-control" name="bussiness_operation_address" placeholder="Operation Address" required></textarea>
						          <span class="help-block">
						          <input type="checkbox" id="bussiness-operation-checkbox"> Same as Registered Address
						          </span>
							    </div>
							</div>
							<div class="form-group">
							<label for="bussiness_operation_state" class="col-sm-4 control-label">Operation State</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussiness_operation_state" placeholder="Operation State" required>
								</div>
							</div>
							<div class="form-group">
							<label for="bussiness_operation_city" class="col-sm-4 control-label">Operation City</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussiness_operation_city" placeholder="Operation City" required>
								</div>
							</div>
							<div class="form-group">
							<label for="bussiness_operation_pin" class="col-sm-4 control-label">Operation PIN</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bussiness_operation_pin" placeholder="Operation PIN" required>
								</div>
							</div>
							<div class="form-group">
							<label for="bussiness_doe" class="col-sm-4 control-label">Date of Establishment</label>
								<div class="col-sm-8">
								  <input type="date" class="form-control" name="bussiness_doe" placeholder="Date of Establishment (MM/DD/YYYY)" required>
								</div>
							</div>
							<div class="form-group">
							<label for="company_cin" class="col-sm-4 control-label">Company CIN</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="company_cin" placeholder="Company CIN">
								  Mandatory for Companies
								</div>
							</div>
							<div class="form-group">
							<label for="company_pan" class="col-sm-4 control-label">Company PAN</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="company_pan" placeholder="Company PAN">
								  Mandatory for Companies
								</div>
							</div>
							<div class="form-group">
							<label for="company_pan_name" class="col-sm-4 control-label">Name on PAN Card</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="company_pan_name" placeholder="Name on PAN (provided above)">
								  Mandatory for Companies
								</div>
							</div>
						</fieldset>
						<fieldset class="step">
							<legend>Promoters Details</legend>
							<div class="form-group">
								<label for="promoter_pan" class="col-sm-4 control-label">PAN of any 1 promoter</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="promoter_pan" placeholder="PAN Number of Promoter" required>
								</div>
							</div>
							<div class="form-group">
								<label for="promoter_pan_name" class="col-sm-4 control-label">Name on PAN Card</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="promoter_pan_name" placeholder="Name on PAN Card" required>
								</div>
							</div>
						</fieldset>
						<fieldset class="step">
							<legend>Bank Account Details (For transfer of settlements)</legend>
							<div class="form-group">
								<label for="bank_name" class="col-sm-4 control-label">Name of Bank</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bank_name" placeholder="Name of Bank" required>
								</div>
							</div>
							<div class="form-group">
								<label for="bank_account_number" class="col-sm-4 control-label">Bank Account Number</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bank_account_number" placeholder="Bank Account Number" required>
								</div>
							</div>
							<div class="form-group">
								<label for="bank_account_name" class="col-sm-4 control-label">Account Holder Name</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bank_account_name" placeholder="Accunt Holder Name" required>
								  Should be same as bussiness/individual name
								</div>
							</div>
							<div class="form-group">
								<label for="bank_account_type" class="col-sm-4 control-label">Bank Account Type</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bank_account_type" placeholder="Bank Account Type e.g. Current, Savings" required>
								</div>
							</div>
							<div class="form-group">
								<label for="bank_branch_address" class="col-sm-4 control-label">Branch Address</label>
								<div class="col-sm-8">
								  <textarea class="form-control" name="bank_branch" placeholder="Name of Bank" required></textarea>
								</div>
							</div>
							<div class="form-group">
								<label for="bank_branch_ifsc" class="col-sm-4 control-label">Branch IFSC Code</label>
								<div class="col-sm-8">
								  <input type="text" class="form-control" name="bank_branch_ifsc" placeholder="IFSC Code of the Bank Branch" required>
								</div>
							</div>
						</fieldset>
						<fieldset class="step">
							<legend>Document Uploads</legend>
							<div class="form-group">
								<label for="bussiness_proof" class="col-sm-4 control-label">Bussiness Proof </label>
								<div class="col-sm-8">
									<h5>
									<span class="help-block">
									  	Upload scan of following(not needed for individual):
										<li>Partnership Agreement (Mandatory, if partnership firm)</li>
										<li>Certificate of Incorporation (Mandatory if private limited)</li>
										<li>Trust/Society/NGO etc. registration proof</li>
									</span>
									</h5>
								  	<input type="file" class="form-control" name="bussiness_proof" >

								</div>
							</div>
							<div class="form-group">
								<label for="bussiness_pan" class="col-sm-4 control-label">Bussiness PAN</label>
								<div class="col-sm-8">
									<h5><span class="help-block">Company Pan Card (Sole Proprietor can use personal PAN)</span></h5>
									<input type="file" class="form-control" name="bussiness_pan" >
								</div>
							</div>
							<div class="form-group">
								<label for="promoter_pan" class="col-sm-4 control-label">Promoter PAN</label>
								<div class="col-sm-8">
									<h5>
									<span class="help-block">
									Upload PAN card scan of at least one promoter, whose details have been filled earlier.
									In case of individual, upload your personal PAN.
									</span>
									</h5>
								  	<input type="file" class="form-control" name="promoter_pan" >
								</div>
							</div>
							<div class="form-group">
								<label for="address_proof" class="col-sm-4 control-label">Address Proof </label>
								<div class="col-sm-8">
									<h5>
									<span class="help-block">
									Upload one of following:
									<li>Electricity Bill (< two months old)</li>
									<li>Telephone Bill (< two months old)</li>
									<li>Bank Account Statement (< two months old)</li>
									</span>
									</h5>
								  	<input type="file" class="form-control" name="address_proof" >
								</div>
							</div>
						</fieldset>
						<fieldset>
						<legend>Submit for Approval</legend>
						<div class="form-group text-center">
							<div class="col-sm-12">
							<h5>
							By Clicking the button below, you have read and understood the terms and conditions and the chargeback policy 
							and agree to abide by them all times.
							</h5>
							<br/><br/>
							<button type="submit" class="btn btn-primary" id="registerButton">Agree & Activate</button>

							</div>
						</div>
						</fieldset>
					</form>
                </div>
				</div>
			</div>
		</div>
	</div>
@stop
