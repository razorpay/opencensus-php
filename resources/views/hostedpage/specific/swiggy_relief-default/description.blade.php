<div id="description-section">
    <div class="heading" style="font-size: 18px;">
        {{$payment_page_data['title']}}
    </div>
    <div class="text-underline"></div>

    @if($fund_type === 'kerala')
        <p>Kerala has been hit by relentless rain for the last two weeks. Rains, floods and landslides have resulted in loss of life and extensive damage in the state.</p>
        <p>Your contribution can go a long way in rebuilding the lives of flood-affected people in Kerala.</p>
        <p>Donations made will not be refunded. Your contribution will be tax exempt under section 80G. The Govt of Kerala will be issuing tax receipts directly to all patrons in due course of time.</p>
        <p>Wish to contribute to the Karnataka Relief Fund instead? <a href="https://pages.razorpay.com/SwiggyReliefKarnataka">Click here</a></p>
    @else
        <p>Kodagu in Karnataka has been hit by relentless rain for the last two weeks. Rains, floods and landslides have resulted in loss of life and extensive damage in the state.</p>
        <p>Your contribution can go a long way in rebuilding the lives of flood-affected people in Kodagu.</p>
        <p>Donations made will not be refunded. Also, Swiggy will not be able to provide donation receipts for claiming tax exemption.</p>
        <p>Wish to contribute to the Kerala Relief Fund instead? <a href="https://pages.razorpay.com/SwiggyRelief">Click here</a></p>
    @endif

    <div class="footer description-footer">
        <a href="https://razorpay.com/" target="_blank">
            Powered by
            <img src="https://cdn.razorpay.com/logo.svg" />
        </a>
    </div>
</div>
