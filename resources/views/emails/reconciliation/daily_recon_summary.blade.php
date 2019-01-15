<!DOCTYPE html>
<html style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Please find reconciliation summary below : </title>
</head>
<body bgcolor="#f6f6f6" style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; -webkit-font-smoothing: antialiased; height: 100%; -webkit-text-size-adjust: none; width: 100% !important; margin: 0; padding: 0;">
<!-- body -->
@foreach ($summary as $entity => $data)
    @if (empty($data['summary']) === false)
        @foreach ($data['summary'] as $date => $gatewayWiseData)
            <b>{{$entity}} Reconciliation summary for {{$date}} - </b><br /><br />
            <table border="1">
                @foreach ($params as $param)
                    <th>{{$param}}</th>
                @endforeach
                @foreach ($gatewayWiseData as $date => $gatewayData)
                    @if ($gatewayData['recon_count_percentage'] >= 100)
                        <tr bgcolor="#A1FF9E">
                    @elseif ($gatewayData['recon_count_percentage'] < 75)
                        <tr bgcolor="#FF9090">
                    @else
                        <tr>
                    @endif

                    @foreach ($params as $param)
                        <td> {{($gatewayData[$param] ?? 0)}}</td>
                    @endforeach
                    </tr>
                @endforeach
                </table><br />
        @endforeach
    @endif
@endforeach
<!-- /body -->
</body>
</html>
