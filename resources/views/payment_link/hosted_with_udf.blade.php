<?php
    $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';

    $payment_page_data          = $data['payment_link'] ?? null;
    $is_test_mode               = $data['is_test_mode'] ?? false;
    $has_udf                    = (empty($udf_schema) === false);
?>


<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
        @include('hostedpage.partials.robot')
        <meta name="description" content="XXXXXXXX">

        @if (isset($payment_page_data))
            <meta property="og:title" content="Payment request by {{$data['merchant']['name']}} for {{$payment_page_data['title']}}">
            <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
            <meta property="og:image:width" content="276px">
            <meta property="og:image:height" content="276px">
            <meta property="og:description" content="Click on this link to pay to {{$data['merchant']['name']}}">
        @endif

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

        @if (isset($data['environment']))
            @if ($data['environment'] !== 'production')
                <script>
                    var Razorpay = {
                        config: {
                            api: "{{ config('app.url') }}/"
                        }
                    }
                </script>
            @endif
        @endif


        <script>
            var data = {!!utf8_json_encode($data)!!};

            var FORM_SCHEMA  = [
              {
                name: 'name',
                type: 'string',
                title: 'Customer Name',
                pattern: '^([a-zA-Z]+ ?)*$',
                minLength: '5',
                maxLength: '10',
                required: true,
                description: 'This is the help text of field, present under Input field',
                options: {
                  // Optional keyword
                  // cmp: 'Input' // Default field if cmp not present
                  value: 'Initialy dummy name', // keyword dynamically inserted if we have seeding data. In case.
                },
              },
              {
                name: 'phone',
                type: 'number',
                title: 'Customer Contact',
                // pattern: '^([0-9]){8,}$', // Pattern restricts typing, so even if valid patter, it will not allow user to type anything
                minLength: 8,
                options: {
                  // cmp: 'Input' // Default field for any component of type:string/number/integer is Input
                  icon: {
                    before: 'i-phone',
                  },
                },
              },
              {
                name: 'amount',
                type: 'number',
                title: 'Amount',
                minimum: '1', // Can be anything (>0) technically
                maximum: '50000000', // Could be user defined max(technically)/ default for amount that we support
                pattern: '^[1-9]+(.([0-9]){1,2})?$',
                options: {
                  // cmp: 'Input' // Default field for any component of type:string/number/integer is Input
                  padded_text: {
                    before: '₹',
                  },
                },
              },
              {
                name: 'field_1',
                type: 'number',
                title: 'Some Counter Field',
                minimum: '2', // Optional
                maximum: '4', // Required keyword, Product wise defines Stock
                options: {
                  cmp: 'Counter', // type:number can be represented as Input.Counter component
                },
              },
              {
                name: 'field_1',
                type: 'number',
                title: 'Dropdown with value as labels',
                enum: [0, 1, 4], // Empty value shouldn't be allowed. First empty value is auto inserted from UI.
                options: {
                  cmp: 'Select', // Default field for type:enum is Select
                  value: 4,
                },
              },
              {
                name: 'field_3',
                type: 'string',
                title: 'Dropdown with custom Labels',
                enum: ['option_0', 'option_1', 'option_2'],
                options: {
                  cmp: 'Select', // Default field for type:enum is Select
                  enum_labels: ['Option Label 0', 'Option Label 1', 'Option Label 2'],
                  // value: 'option_2',
                },
              },
            ];

            var templateData = {
                schema: FORM_SCHEMA,
                data: {
                  is_test_mode: true,
                  merchant: {
                    name: 'Dummy Merchant Name',
                    brand_color: '#4f8cf3',
                    image:
                      'https://dummyimage.com/055aa0/ffffff/300x300&text=Merchant%20Logo',
                  },
                  payment_page_data: {
                    title: 'Invoice and Bill Payments',
                    description:
                      "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, A when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type a A  And scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and  A  A typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.  AIt has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I",
                    social_share: 1,
                    support: {
                      email: 'support@savethewhales.org',
                      phone: '1800-1234-1323 (Timings: 9AM to 6PM)',
                    },
                    terms:
                      'If payment fails, we give free even ticket within 4 days. Enjoy!',
                  },
                },
                context: {
                  page_title: 'Invoice and Bill Payments',
                  form_title: 'Payment Details',
                  isEditMode: false, // should be true for dashboard
                },
              };

            function renderPaymentPage() {
                window.RZP.renderApp('paymentpage-container', templateData);
            }
        </script>

        <script src="https://cdn.razorpay.com/static/analytics/bundle.js" defer></script>
        <script src="{{env('AWS_CF_CDN_URL')}}/static/hosted/wysiwyg.js" onload="renderPaymentPage()" async defer></script>
        <script src="https://checkout.razorpay.com/v1/checkout.js" async defer></script>
    </head>

    <body>
        <div id="paymentpage-container">
        </div>
    </body>
</html>
