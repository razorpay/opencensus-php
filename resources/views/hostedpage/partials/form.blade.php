<div id="form-section">
    @if($is_test_mode)
        <div id="testmode-warning">
            This payment request is created in <b>Test Mode</b>. Only test payments can be made for this.
        </div>
    @endif

    <div class="heading">
        Enter Payment Details
    </div>

    <div id="udf_parent">
        <div id="udf_container">
        </div>
    </div>

    <div class="footer form-footer">
        <button type="button" class="btn" id="udf_submit_btn">
            Proceed to Pay <span>→</span>
        </button>
    </div>

    <button type="button" class="btn btn--full mobile-submit-btn" id="udf_submit_btn">
        <svg id="secure-lock-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            <path d="M0 0h24v24H0z" fill="none"/>
            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
        </svg>
        PAY SECURELY
    </button>


    @include('hostedpage.partials.footer')
</div>
