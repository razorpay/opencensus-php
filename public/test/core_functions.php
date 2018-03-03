<?php

$details = get_input_details($_POST, $messages);

if ($details and isset($details->action))
{
    if ($details->action === 'capture')
    {
        capture_payment($details, $messages);
    }
    elseif($details->action === 'refund')
    {
        refund_payment($details, $messages);
    }
}

function capture_payment($input, &$messages)
{
    $url = PRIVATE_URL."/payments/$input->id/capture";
    $data = [
        'amount' => $input->amount,
    ];

    $response = simple_curl($url, 'post', $data);

    handle_request($response, $messages) === true ?
        ($messages['success'][] = "Payment: $input->id successfully captured for $input->amount") : '';
}

function refund_payment($input, &$messages)
{
    $url = PRIVATE_URL."/payments/$input->id/refund";
    $data = [
        'amount' => $input->amount,
    ];

    $response = simple_curl($url, 'post', $data);

    handle_request($response, $messages) === true ?
        ($messages['success'][] = "Payment: $input->id successfully refunded for $input->amount") : '';
}

function get_input_details($input, &$messages)
{
    $action = $input['action'] ?? null;
    if (empty($action))
    {
        return;
    }
    $paymentId = $input['payment_id'] ?? null;
    if (empty($paymentId))
    {
        return ($messages['failure'][] = 'Empty Payment Id');
    }
    $amount = intval($input['amount'] ?? null);
    if ($amount < 100)
    {
        return ($messages['failure'][] = 'Invalid Amount'. $amount);
    }

    return (object) ['id' => $paymentId, 'amount' => $amount, 'action' => $action];
}

function get_last_payments(array $input, &$messages)
{
    try
    {
        $url = PRIVATE_URL.'/payments?count=10&';
        if (empty($input) === false)
        {
            $url.=http_build_query($input);
        }

        $response = simple_curl($url);

        if (handle_request($response, $messages))
        {
            return $response->items;
        }
    }
    catch(\Exception $e)
    {
        $messages['failure'][] = $e->getMessage();
    }

    return [];
}

function handle_request(&$response, &$messages)
{
    if (empty($response['error']) === false)
    {
        $messages['failure'][] = $response['error'];

        return false;
    }
    $response = json_decode($response['content']);
    if (empty($response) === true)
    {
        $messages['failure'][] = json_last_error();

        return false;
    }

    if(isset($response->error) and isset($response->error->description))
    {
        $messages['failure'][] = $response->error->description;

        return false;
    }

    return true;
}

function simple_curl($uri, $method='GET', $data=null, $curl_headers=array(), $curl_options=array()) {
    // defaults
    $default_curl_options = array(
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    );
    $default_headers = array();

    // validate input
    $method = strtoupper(trim($method));
    $allowed_methods = array('GET', 'POST', 'PUT', 'DELETE');

    if(!in_array($method, $allowed_methods))
        throw new \Exception("'$method' is not valid cURL HTTP method.");

    if(!empty($data) && !is_string($data))
        $data = http_build_query($data);

    // init
    $curl = curl_init($uri);

    // apply default options
    curl_setopt_array($curl, $default_curl_options);

    // apply method specific options
    switch($method) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case 'PUT':
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case 'DELETE':
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            break;
    }

    // apply user options
    curl_setopt_array($curl, $curl_options);

    // add headers
    curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge($default_headers, $curl_headers));

    // parse result
    $raw = rtrim(curl_exec($curl));

    $lines = explode("\r\n", $raw);
    $headers = array();
    $content = '';
    $write_content = false;
    if(count($lines) > 3) {
        foreach($lines as $h) {
            if($h == '')
                $write_content = true;
            else {
                if($write_content)
                    $content .= $h."\n";
                else
                    $headers[] = $h;
            }
        }
    }

    $error = curl_error($curl);

    curl_close($curl);

    return [
        'content'   => $content,
        'error'     => $error,
    ];
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) : 'just now';
}

