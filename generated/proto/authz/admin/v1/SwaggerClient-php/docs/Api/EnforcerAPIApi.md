# Swagger\Client\EnforcerAPIApi

All URIs are relative to *https://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**enforcerAPIEnforce**](EnforcerAPIApi.md#enforcerAPIEnforce) | **POST** /v1/enforce | Enforce does the policy enforcement for a particular request.
[**enforcerAPIGetImplicitPermissions**](EnforcerAPIApi.md#enforcerAPIGetImplicitPermissions) | **POST** /v1/implicit_permissions | GetImplicitPermissions returns the list of all permissions for a particular subject. This resolves the complete role hierarchy and returns the effective permissions.


# **enforcerAPIEnforce**
> \Swagger\Client\Model\V1EnforceResponse enforcerAPIEnforce($body)

Enforce does the policy enforcement for a particular request.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\EnforcerAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \Swagger\Client\Model\V1EnforceRequest(); // \Swagger\Client\Model\V1EnforceRequest | 

try {
    $result = $apiInstance->enforcerAPIEnforce($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnforcerAPIApi->enforcerAPIEnforce: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\Swagger\Client\Model\V1EnforceRequest**](../Model/V1EnforceRequest.md)|  |

### Return type

[**\Swagger\Client\Model\V1EnforceResponse**](../Model/V1EnforceResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **enforcerAPIGetImplicitPermissions**
> \Swagger\Client\Model\V1GetImplicitPermissionsResponse enforcerAPIGetImplicitPermissions($body)

GetImplicitPermissions returns the list of all permissions for a particular subject. This resolves the complete role hierarchy and returns the effective permissions.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\EnforcerAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \Swagger\Client\Model\V1InternalRequest(); // \Swagger\Client\Model\V1InternalRequest | 

try {
    $result = $apiInstance->enforcerAPIGetImplicitPermissions($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnforcerAPIApi->enforcerAPIGetImplicitPermissions: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\Swagger\Client\Model\V1InternalRequest**](../Model/V1InternalRequest.md)|  |

### Return type

[**\Swagger\Client\Model\V1GetImplicitPermissionsResponse**](../Model/V1GetImplicitPermissionsResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

