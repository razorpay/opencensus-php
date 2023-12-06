<div class='content-container'>
    <p class='content-head'>Terms &amp; Conditions</p>
    <div class='content-seprater'></div>
    <p class='updated-date'>Last updated on {{{$data['updated_at']}}}</p>
    <p class='content-text'>
        For the purpose of these Terms and Conditions, The term "we", "us",
        "our" used anywhere on this page shall mean
        {{$data['merchant_legal_entity_name']}}, whose registered/operational
        office is
        @if(isset($data['merchant_details']['business_registered_address']))
            {{ $data['merchant_details']['business_registered_address'] }}
            {{ $data['merchant_details']['business_registered_city'] }}
            {{{RZP\Constants\IndianStates::getStateName($data['merchant_details']['business_registered_state'])}}}
            {{ $data['merchant_details']['business_registered_pin'] }}
        @else
            @if(isset($data['merchant_details']['business_operation_address']))
                {{ $data['merchant_details']['business_operation_address'] }}
                {{ $data['merchant_details']['business_operation_city'] }}
                {{{RZP\Constants\IndianStates::getStateName($data['merchant_details']['business_registered_state'])}}}
                {{ $data['merchant_details']['business_operation_pin'] }}
            @endif
        @endif
        . "you", “your”, "user", “visitor” shall
        mean any natural or legal person who is visiting our website and/or
        agreed to purchase from us.
    </p>
    <p class='content-text'>
        <strong>Your use of the website and/or purchase from us are governed by following Terms and Conditions:</strong>
    </p>
    <ul class='unorder-list'>
        <li class='list-item'>
            <p class='content-text list-text'>
                The content of the pages of this website is subject to change without notice.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                Neither we nor any third parties provide any warranty or
                guarantee as to the accuracy, timeliness, performance,
                completeness or suitability of the information and materials
                found or offered on this website for any particular purpose.
                You acknowledge that such information and materials may contain
                inaccuracies or errors and we expressly exclude liability for
                any such inaccuracies or errors to the fullest extent permitted
                by law.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                Your use of any information or materials on our website and/or product
                pages is entirely at your own risk, for which we shall not be liable.
                It shall be your own responsibility to ensure that any products, services
                or information available through our website and/or product pages meet your
                specific requirements.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                Our website contains material which is owned by or licensed to us. This material
                includes, but are  not limited to, the design, layout, look, appearance
                and graphics. Reproduction is prohibited other than in accordance with the
                copyright notice, which forms part of these terms and conditions.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                All trademarks reproduced in our website which are not the property of, or
                licensed to, the operator are acknowledged on the website.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                Unauthorized use of information provided by us  shall give rise to a claim for
                damages and/or be a criminal offense.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                From time to time our website may also include links to other websites. These links are
                provided for your convenience to provide further information.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                You may not create a link to our website from another website or document without
                {{$data["merchant_legal_entity_name"]}}’s prior written consent.
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                Any dispute arising out of use of our website and/or purchase with
                us and/or any engagement with us is subject to the laws of India .
            </p>
        </li>
        <li class='list-item'>
            <p class='content-text list-text'>
                We, shall be under no liability whatsoever in respect of any loss or
                damage arising directly or indirectly out of the decline of
                authorization for any Transaction, on Account of the Cardholder
                having exceeded the preset limit mutually agreed by us with our
                acquiring bank from time to time
            </p>
        </li>
    </ul>
</div>
