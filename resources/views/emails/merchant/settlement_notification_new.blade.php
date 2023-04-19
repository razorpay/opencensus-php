<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
​
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
</head>
​
<body style="font-family: Trebuchet MS">
<div
    style="
        background-image: linear-gradient(
          to bottom,
          #1b3fa6 0%,
          #1b3fa6 200px,
          #f8f9f9 200px,
          #f8f9f9 90%
        );
        height: 100%;
      "
>
    <!-- Razorpay logo -->
    <div style="text-align: center; margin-bottom: 30px">
        @if ($custom_branding === false)
            <img
                style="margin-top: 30px; height: 30px"
                src={{ $org_data['logo_url'] }}
            />
        @endif
    </div>
    ​
    <div style="max-width: 588px; margin: auto">
        <!-- Settlement report and header -->
        <div style="max-width: 588px; margin: auto">
            <div
                style="
              background-color: #ffffff;
              margin-bottom: 20px;
              padding: 20px;
              max-width: 550px;
              text-align: center;
              margin-left: auto;
              margin-right: auto;
            "
            >
                <h4
                    style="
                color: #0d2366;
                font-family: Trebuchet MS;
                font-style: normal;
                font-weight: bold;
                font-size: 25px;
                line-height: 24px;
                margin-top: 12px;
              "
                >
                    Settlement Report
                </h4>
            </div>
            <div
                style="
              background-color: #ffffff;
              margin-bottom: 6px;
              padding: 20px;
              max-width: 550px;
              text-align: center;
              margin-left: auto;
              margin-right: auto;
              border-top-width: medium;
              border-top-style: solid;
              border-top-color: #0eb550;
            "
            >
                @if($merchant['logo_url'])
                    <img style="height: 60px;" src={{$merchant['logo_url']}} />
                    <p
                        style="
                  font-family: Trebuchet MS;
                  font-style: normal;
                  font-size: 18px;
                  line-height: 25px;
                  text-align: center;
                  color: #515978;
                  padding-top: 20px;
                  border-top-style: solid;
                  border-top-color: #ebedf2;
                  border-top-width: 2px;
                "
                    >
                        <strong
                        >{{ $org_data['currency_logo'] }} {{number_format(floatval($settlement['amount']/100),
                  2, '.', '')}}</strong
                        >
                        amount has been successfully settled to your bank account on
                        {{$settlement['time']}}
                    </p>
                @else
                    <p
                        style="
                  font-family: Trebuchet MS;
                  font-style: normal;
                  font-size: 18px;
                  line-height: 25px;
                  text-align: center;
                  color: #515978;
                "
                    >
                        <strong
                        >{{ $org_data['currency_logo'] }} {{number_format(floatval($settlement['amount']/100),
                  2, '.', '')}}</strong
                        >
                        amount has been successfully settled to your bank account on
                        {{$settlement['time']}}
                    </p>
                @endif
            </div>
        </div>
        ​
        <!-- Settlement Details  -->
        <div style="max-width: 588px; margin: auto">
            <div
                style="
              background-color: #ffffff;
              padding: 20px 20px 2px 20px;
              max-width: 550px;
              margin-left: auto;
              margin-right: auto;
              margin-top: 15px;
              font-size: 15px;
            "
            >
                <div
                    style="
                display: flex;
                font-size: 17px;
                padding: 15px 0px;
                border-bottom: 1px solid #ebedf2;
                margin-bottom: 20px;
              "
                >
                    <div
                        style="
                  width: 50%;
                  text-align: start;
                  padding-left: 8px;
                  color: #323438;
                "
                    >
                        Settlement details
                    </div>
                    @if ($org_data['settelement_guide'] === true)
                        <div style="width: 50%; text-align: end; padding-right: 10px">
                            <a
                                href="https://razorpay.com/settlement/"
                                style="color: #528ff0; text-decoration: none"
                                target="_blank"
                            >View settlement guide
                            </a>
                        </div>
                    @endif
                </div>
                <table style="background-color: white; width: 100%">
                    <tbody>
                    <tr>
                        <td
                            style="
                      color: #7b8199;
                      text-align: start;
                      padding: 0px 0px 20px 10px;
                    "
                        >
                            Settlement Id
                        </td>
                        <td style="text-align: end; padding: 0px 10px 20px 0px">
                            {{$settlement['id']}}
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="
                      color: #7b8199;
                      text-align: start;
                      padding: 0px 0px 20px 10px;
                    "
                        >
                            Amount Settled
                        </td>
                        <td style="text-align: end; padding: 0px 10px 20px 0px">
                            {{ $org_data['currency_logo'] }} {{number_format(floatval($settlement['amount']/100), 2, '.','')}}
                        </td>
                    </tr>
                    @if($org_data['utr'] === true)
                        <tr>
                            <td
                                style="
                          color: #7b8199;
                          text-align: start;
                          padding: 0px 0px 20px 10px;
                        "
                            >
                                UTR
                            </td>
                            <td style="text-align: end; padding: 0px 10px 20px 0px">
                                {{$settlement['utr']}}
                            </td>
                        </tr>
                    @endif
                    @if($org_data['acc_no'] === true)
                        <tr>
                            <td
                                style="
                          color: #7b8199;
                          text-align: start;
                          padding: 0px 0px 20px 10px;
                        "
                            >
                                Account No
                            </td>
                            <td style="text-align: end; padding: 0px 10px 20px 0px">
                                {{$settlement['ba_number']}}
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
                <div style="background: white; margin-top: -20px">
                    <p
                        style="
                  font-size: 18px;
                  line-height: 25px;
                  text-align: center;
                  color: #515978;
                  padding: 20px 0px 0px 0px;
                  border-top-style: solid;
                  border-top-color: #ebedf2;
                  border-top-width: 2px;
                  background-color: white;
                "
                    >
                        Settlement was made on {{$settlement['time']}}
                    </p>
                </div>
            </div>
        </div>
        ​
        <!-- Settlement Breakup -->
        <div style="max-width: 588px; margin: auto">
            <div
                style="
              background-color: #ffffff;
              padding: 20px 20px 2px 20px;
              max-width: 550px;
              margin-left: auto;
              margin-right: auto;
              margin-top: 15px;
              font-size: 15px;
            "
            >
                <p
                    style="
                font-family: Trebuchet MS;
                font-style: normal;
                font-size: 18px;
                line-height: 25px;
                color: #515978;
                text-align: center;
                margin-top: 0px;
                border-bottom-style: solid;
                border-bottom-color: #ebedf2;
                border-bottom-width: 2px;
                padding-bottom: 18px;
              "
                >
                    Breakup for Settlement
                </p>
                <table
                    style="background-color: white; width: 100%; border-spacing: 0px"
                >
                    <thead style="color: #7b8199">
                    <tr>
                        <th
                            style="
                      padding: 0px 0px 20px 0px;
                      text-align: start;
                      border-bottom: 1px solid #ebedf2;
                    "
                        >
                            Component
                        </th>
                        <th style="
                      padding: 0px 0px 20px 0px;
                      text-align: center;
                      border-bottom: 1px solid #ebedf2;
                    "
                        >
                            Type
                        </th>
                        <th
                            style="
                      padding: 0px 0px 20px 0px;
                      text-align: center;
                      border-bottom: 1px solid #ebedf2;
                    "
                        >
                            Amount
                        </th>
                        <th
                            style="
                      padding: 0px 0px 20px 0px;
                      text-align: center;
                      border-bottom: 1px solid #ebedf2;
                    "
                        >
                            Fee
                        </th>
                        <th
                            style="
                      padding: 0px 10px 20px 0px;
                      text-align: center;
                      border-bottom: 1px solid #ebedf2;
                    "
                        >
                            Tax
                        </th>
                        <th
                            style="
                      padding: 0px 0px 20px 0px;
                      text-align: center;
                      border-bottom: 1px solid #ebedf2;
                    "
                        >
                            Settled Amount
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    @for ($i = 0; $i < $settlement['breakup']['count']; $i++)
                        <tr>
                            <td style="text-align: start; padding: 10px 10px 20px 0px">
                                {{ucfirst(preg_split('~_(?=[^_]*$)~', $settlement['breakup']['items'][$i]['component'])[0])}}
                                <span style="color: gray; font-weight: lighter">
                      @if ((ends_with($settlement['breakup']['items'][$i]['component'], "_international")) === true)
                                        (INT)
                                    @elseif ((ends_with($settlement['breakup']['items'][$i]['component'], "_domestic")) === true)
                                        (DOM)
                                    @endif
                    </span>
                            </td>
                            <td style="text-align: center; padding: 10px 10px 20px 0px">
                                {{$settlement['breakup']['items'][$i]['type']}}
                            </td>
                            <td style="text-align: center; padding: 10px 10px 20px 0px">
                                {{ $org_data['currency_logo'] }} {{number_format(floatval($settlement['breakup']['items'][$i]['amount']/100), 2, '.','')}}
                            </td>
                            <td style="text-align: center; padding: 10px 10px 20px 0px">
                                {{ $org_data['currency_logo'] }} {{number_format(floatval($settlement['breakup']['items'][$i]['fee']/100),2, '.', '')}}
                            </td>
                            <td style="text-align: center; padding: 10px 10px 20px 0px">
                                {{ $org_data['currency_logo'] }} {{number_format(floatval($settlement['breakup']['items'][$i]['tax']/100),2, '.', '')}}
                            </td>
                            <td style="text-align: center; padding: 10px 10px 20px 0px">
                                @if ($settlement['breakup']['items'][$i]['type'] === 'debit')
                                    {{ $org_data['currency_logo'] }} {{number_format(floatval((-1*$settlement['breakup']['items'][$i]['amount'] - $settlement['breakup']['items'][$i]['fee'] - $settlement['breakup']['items'][$i]['tax'])/100),2, '.', '')}}
                                @else
                                    {{ $org_data['currency_logo'] }} {{number_format(floatval(($settlement['breakup']['items'][$i]['amount'] - $settlement['breakup']['items'][$i]['fee'] - $settlement['breakup']['items'][$i]['tax'])/100),2, '.', '')}}
                                @endif
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
                <div
                    style="
                display: flex;
                font-size: 17px;
                padding: 15px 0px;
                border-top: 1px solid #ebedf2;
              "
                >
                    <div
                        style="
                  width: 50%;
                  text-align: start;
                  padding-left: 8px;
                  color: #7b8199;
                "
                    >
                        Total settled amount
                    </div>
                    <div
                        style="
                  width: 50%;
                  text-align: end;
                  padding-right: 53px;
                  color: #7b8199;
                "
                    >
                        {{ $org_data['currency_logo'] }} {{number_format(floatval($settlement['amount']/100), 2, '.','')}}
                    </div>
                </div>
            </div>
        </div>
        ​
        <!-- Login to dashboard -->
        <div style="max-width: 588px; margin: auto">
            <div
                style="
              text-align: center;
              margin-bottom: 16px;
              margin-top: 8px;
              max-width: 588px;
              margin: auto;
            "
            >
                <a
                    href="{{$settlement['url']}}"
                    target="_blank"
                    style="color: white; text-decoration: unset"
                >
                    <div
                        style="
                  padding: 15px 0px 15px 0px;
                  background: #528ff0;
                  border-radius: 3px;
                  margin: 10px 0px;
                  color: white;
                "
                    >
                        View Settlement
                    </div>
                </a>
            </div>
            @if($org_data['raise_req_on_email'] === false)
                <p style="font-size: 14px; text-align: center; color: #7b8199">
                    If you have any issue with the service from {{$org_data['org_name']}}, please raise
                    your request
                    <a href={{ $org_data['raise_req_redirect'] }}
                    >here</a
                    >
                </p>
            @else
                <p style="font-size: 14px; text-align: center; color: #7b8199">
                    If you have any issue with the service from {{$org_data['org_name']}}, please raise
                    your request
                    <a class="link" href="mailto:{{ $org_data['raise_req_redirect'] }}" style="text-decoration: none; color: #528FF0;"
                    >{{ $org_data['raise_req_redirect'] }}</a
                    >.
                </p>
            @endif
        </div>
        @if ($custom_branding)
            <div style="text-align: center; margin-top: 20px; margin-bottom: 5px">
                <img src="{{ $email_logo }}" style="height: 32px;" />
            </div>
        @endif
    </div>
</div>
</body>
​
</html>
