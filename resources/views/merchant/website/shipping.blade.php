<div class='content-container'>
    <p class='content-head'>Shipping &amp; Delivery Policy</p>
    <div class='content-seprater'></div>
    <p class='updated-date'>Last updated on {{{$data['updated_at']}}}</p>

    @if ($data['website_detail']['shipping_period'] !== 'Not applicable')
        <p class='content-text'>
            For International buyers, orders are shipped and delivered through
            registered international courier companies and/or International speed post
            only. For domestic buyers, orders are shipped through registered domestic
            courier companies and /or speed post only. Orders are shipped within
            {{{$data['website_detail']['shipping_period']}}} or as per the delivery date
            agreed at the time of order confirmation and delivering of the shipment
            subject to Courier Company / post office norms.
            {{{$data['merchant_legal_entity_name']}}} is not liable for any delay in
            delivery by the courier company / postal authorities and only guarantees to
            hand over the consignment to the courier company or postal authorities
            within {{{$data['website_detail']['shipping_period']}}} from the date of the
            order and payment or as per the delivery date agreed at the time of order
            confirmation. Delivery of all orders will be to the address provided by the
            buyer. Delivery of our services will be confirmed on your mail ID as specified
            during registration. For any issues in utilizing our services you may contact our
            helpdesk on {{{$data['website_detail']['additional_data']['support_contact_number']}}}
            or {{{$data['website_detail']['additional_data']['support_email']}}}
        </p>
    @endif

    @if ($data['website_detail']['shipping_period'] === 'Not applicable')
        <p class='content-text'>Shipping is not applicable for business.</p>
    @endif
</div>
