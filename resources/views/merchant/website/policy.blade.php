<!DOCTYPE html>
<html>
<head>
    <!-- Responsive meta tag -->
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <style>
        body { margin: 0; }
        .header-conatiner { display: flex; align-items: center; padding: 24px; }
        .header-text { font-size: 18px; line-height: 24px; color: #fff; margin: 0 0 0 18px; }
        .compalinace-page { background: #0a1d38; font-family: Lato, sans-serif; font-style: normal; font-weight: 400; }
        @media screen and (min-width: 768px) { .content-container { margin: 0 auto; max-width: 1000px; } .header-conatiner { margin: 0 auto; max-width: 1000px; } }
        .compalinace-content { background: #ffffff; border-radius: 32px 32px 0 0; padding: 32px 24px; }
        .content-head { font-size: 24px; line-height: 30px; color: #213554; margin: 0; font-weight: 700;}
        .content-seprater { width: 28px; height: 5px; background-color: #213554; margin-top: 16px; }
        .updated-date { margin: 16px 0 0; color: #213554ab; font-weight: 700;}
        .content-text { color: #515978; margin: 16px 0 0; }
        .content-text { font-size: 14px; line-height: 20px; }
        .merchant-logo { width: 74.6px; height: 64px; background: #fefefe; box-shadow: 0px 0px 8px #00000040; border-radius: 8px; display: flex; justify-content: center; align-items: center; }
        @media screen and (max-width: 330px) { .merchant-logo { width: 87px; } }
        @media all and (min-width: 768px), (min-width: 383px) { .merchant-logo { width: 64px; } }
        .logo-container { display: flex; justify-content: center; align-items: center; width: 50px; height: 50px; background: #0a1d38; box-shadow: 11.0003px 22.0007px 53.9016px rgba(0, 0, 0, 0.1), 22.0007px 11.0003px 53.9854px -2.03719px rgba(255, 255, 255, 0.1); border-radius: 50%; }
        .logo-text {color: #fff; font-weight: 700; font-size: 32px; }
        .list-item { display: list-item; padding-left: 5px; }
        .unorder-list { margin: 0; }
        .list-text { margin-top: 8px; }
    </style>
    @if ($data['public'] === false)
        <style>
            .content-head { font-size: 20px; }
        </style>
    @endif
    @if ($data['public'] === true)
        <style>
            .compalinace-page {
                background: none;
            }
            .content-container { margin: initial; max-width: initial; }
        </style>
    @endif
</head>
<div class= 'compalinace-page'>
    @if ($data['public'] === false)
        <div class= 'header-conatiner'>
            @empty($data['logo_url'])
            <div class= 'merchant-logo'>
                <div class= 'logo-container'><div class= 'logo-text'>{{{substr(($data['merchant_details']['business_name']??$data['merchant']['name']),0,1)}}}</div></div>
            </div>
            @endisset
            @isset ($data['logo_url'])
                    <img src='{{{$data['logo_url']}}}' style='height:64px;width:64px'/>
            @endif
                <p class= 'header-text'>{{{($data['merchant_details']['business_name']??$data['merchant']['name'])}}}</p>
        </div>
    @endif
        <div class='compalinace-content'>
            @if ($data['sectionName'] === 'contact_us')
                @include('merchant.website.contact_us',$data) @endif
            @if ($data['sectionName'] === 'privacy')
                @include('merchant.website.privacy',$data) @endif
            @if ($data['sectionName']=== 'refund')
                @include('merchant.website.refund',$data) @endif
            @if ($data['sectionName'] === 'shipping')
                @include('merchant.website.shipping',$data) @endif
            @if ($data['sectionName']=== 'terms')
                @include('merchant.website.terms',$data) @endif
        </div>
</div>
</html>
