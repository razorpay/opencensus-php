<div class='content-container'>
    <p class='content-head'>Cancellation &amp; Refund Policy</p>
    <div class='content-seprater'></div>
    <p class='updated-date'>Last updated on {{{$data['updated_at']}}}</p>
    @if ($data['website_detail']['refund_process_period'] !== 'Not applicable')
        <p class='content-text'>
        {{{$data['merchant_legal_entity_name']}}} believes in helping its customers
        as far as possible, and has therefore a liberal cancellation policy. Under
        this policy:
    </p>
    <ul class='unorder-list'>
        <li class='list-item'>
            <p class='content-text list-text'>
                Cancellations will be considered only if the request is made immediately
                after placing the order. However, the cancellation request may not be
                entertained if the orders have been communicated to the vendors/merchants
                and they have initiated the process of shipping them.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                {{{$data['merchant_legal_entity_name']}}} does not accept cancellation
                requests for perishable items like flowers, eatables etc. However,
                refund/replacement can be made if the customer establishes that the
                quality of product delivered is not good.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                In case of receipt of damaged or defective items please report the same
                to our Customer Service team. The request will, however, be entertained
                once the merchant has checked and determined the same at his own end.
                This should be reported within
                {{{$data['website_detail']['refund_request_period']}}} of receipt of the
                products.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                In case you feel that the product received is not as shown on the site
                or as per your expectations, you must bring it to the notice of our
                customer service within
                {{{$data['website_detail']['refund_request_period']}}} of receiving the
                product. The Customer Service Team after looking into your complaint
                will take an appropriate decision.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                In case of complaints regarding products that come with a warranty from
                manufacturers, please refer the issue to them.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                In case of any Refunds approved by the
                {{{$data['merchant_legal_entity_name']}}}, it’ll take
                {{{$data['website_detail']['refund_process_period']}}} for the refund to
                be processed to the end customer.
            </p>
        </li>
    </ul>
    @endif
    @if ($data['website_detail']['refund_process_period'] === 'Not applicable')
    <p class='content-text'>No cancellations &amp; Refunds are entertained</p>
    @endif
</div>
