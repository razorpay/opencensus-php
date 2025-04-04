# AuthzAdmin\Client\AdminAPIApi

All URIs are relative to *https://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**adminAPICreateAction**](AdminAPIApi.md#adminAPICreateAction) | **POST** /v1/actions | CreateAction creates the action entity in AuthZ policy store.
[**adminAPICreatePermission**](AdminAPIApi.md#adminAPICreatePermission) | **POST** /v1/permissions | CreatePermission creates the permission entity in AuthZ policy store.
[**adminAPICreatePolicy**](AdminAPIApi.md#adminAPICreatePolicy) | **POST** /v1/policies | CreatePolicy creates the policy entity in AuthZ policy store.
[**adminAPICreatePrivilege**](AdminAPIApi.md#adminAPICreatePrivilege) | **POST** /v1/privileges | CreatePrivilege creates the privilege entity in AuthZ policy store.
[**adminAPICreatePrivilegeRoleMapping**](AdminAPIApi.md#adminAPICreatePrivilegeRoleMapping) | **POST** /v1/privilege_role_mappings | CreatePrivilegeRoleMapping creates the privilege role mapping in AuthZ policy store.
[**adminAPICreateResource**](AdminAPIApi.md#adminAPICreateResource) | **POST** /v1/resources | CreateResource creates the resource entity in AuthZ policy store.
[**adminAPICreateResourceGroup**](AdminAPIApi.md#adminAPICreateResourceGroup) | **POST** /v1/resource_groups | CreateResourceGroup creates the resource group entity in AuthZ policy store.
[**adminAPICreateResourceGroupMapping**](AdminAPIApi.md#adminAPICreateResourceGroupMapping) | **POST** /v1/resource_group_mappings | CreateResourceGroupMapping creates the resource group mapping in AuthZ policy store.
[**adminAPICreateRole**](AdminAPIApi.md#adminAPICreateRole) | **POST** /v1/roles | CreateRole creates the role entity in AuthZ policy store.
[**adminAPICreateRolePolicyMapping**](AdminAPIApi.md#adminAPICreateRolePolicyMapping) | **POST** /v1/role_policy_mappings | CreateRolePolicyMapping adds a policy to the given role in AuthZ policy store.
[**adminAPICreateService**](AdminAPIApi.md#adminAPICreateService) | **POST** /v1/services | CreateService creates the service entity in AuthZ policy store.
[**adminAPICreateSubjectRoleMapping**](AdminAPIApi.md#adminAPICreateSubjectRoleMapping) | **POST** /v1/subject_role_mappings | CreateSubjectRoleMapping assigns the role to a subject entity in AuthZ policy store.
[**adminAPIDeleteAction**](AdminAPIApi.md#adminAPIDeleteAction) | **DELETE** /v1/actions/{id} | DeleteAction deletes the action entity from AuthZ policy store.
[**adminAPIDeletePermission**](AdminAPIApi.md#adminAPIDeletePermission) | **DELETE** /v1/permissions/{id} | DeletePermission deletes the permission entity from AuthZ policy store.
[**adminAPIDeletePolicy**](AdminAPIApi.md#adminAPIDeletePolicy) | **DELETE** /v1/policies/{id} | DeletePolicy deletes the policy entity from AuthZ policy store.
[**adminAPIDeleteResource**](AdminAPIApi.md#adminAPIDeleteResource) | **DELETE** /v1/resources/{id} | DeleteResource deletes the resource entity from AuthZ policy store.
[**adminAPIDeleteResourceGroup**](AdminAPIApi.md#adminAPIDeleteResourceGroup) | **DELETE** /v1/resource_groups/{id} | DeleteResourceGroup deletes the resource group entity from AuthZ policy store.
[**adminAPIDeleteResourceGroupMapping**](AdminAPIApi.md#adminAPIDeleteResourceGroupMapping) | **DELETE** /v1/resource_group_mappings | DeleteResourceGroupMapping deletes the resource group mapping from AuthZ policy store.
[**adminAPIDeleteRole**](AdminAPIApi.md#adminAPIDeleteRole) | **DELETE** /v1/roles/{id} | DeleteRole deletes the role entity from AuthZ policy store.
[**adminAPIDeleteRolePolicyMapping**](AdminAPIApi.md#adminAPIDeleteRolePolicyMapping) | **DELETE** /v1/role_policy_mappings | DeleteRolePolicyMapping deletes a policy from the given role in AuthZ policy store.
[**adminAPIDeleteService**](AdminAPIApi.md#adminAPIDeleteService) | **DELETE** /v1/services/{id} | DeleteService deletes the service entity from AuthZ policy store.
[**adminAPIDeleteSubject**](AdminAPIApi.md#adminAPIDeleteSubject) | **DELETE** /v1/subjects | DeleteSubject detaches all roles for the given subject entity in AuthZ policy store.
[**adminAPIDeleteSubjectRoleMapping**](AdminAPIApi.md#adminAPIDeleteSubjectRoleMapping) | **DELETE** /v1/subject_role_mappings | DeleteSubjectRoleMapping detaches the role from a subject entity in AuthZ policy store.
[**adminAPIGetRole**](AdminAPIApi.md#adminAPIGetRole) | **GET** /v1/roles/{identifier} | GetRole returns the role entity from AuthZ policy store.
[**adminAPIListAction**](AdminAPIApi.md#adminAPIListAction) | **GET** /v1/actions | ListAction returns a list of actions based on the supplied filters.
[**adminAPIListPermission**](AdminAPIApi.md#adminAPIListPermission) | **GET** /v1/permissions | ListPermission returns a list of permissions satisfying the filter conditions.
[**adminAPIListPolicy**](AdminAPIApi.md#adminAPIListPolicy) | **GET** /v1/policies | ListPolicy returns a list of policies satisfying the filter conditions.
[**adminAPIListPrivileges**](AdminAPIApi.md#adminAPIListPrivileges) | **GET** /v1/privileges | ListPrivileges return a list of privileges for the given orgId
[**adminAPIListResource**](AdminAPIApi.md#adminAPIListResource) | **GET** /v1/resources | ListResource returns a list of resources based on the supplied filters.
[**adminAPIListResourceGroup**](AdminAPIApi.md#adminAPIListResourceGroup) | **GET** /v1/resource_groups | ListResourceGroup returns a list of resource group entities from AuthZ policy store.
[**adminAPIListRole**](AdminAPIApi.md#adminAPIListRole) | **GET** /v1/roles | ListRole returns a list of roles matching the filter condition.
[**adminAPIListService**](AdminAPIApi.md#adminAPIListService) | **GET** /v1/services | ListService returns a list of services based on the supplied filters.
[**adminAPIMigrateRole**](AdminAPIApi.md#adminAPIMigrateRole) | **POST** /v1/roles_migrate | MigrateRole create role entities with pre-defined IDs. This will be decommissioned after CAC migration
[**adminAPIRecon**](AdminAPIApi.md#adminAPIRecon) | **POST** /v1/recon | Recon is to be used for reconciliation of policies between MySQL &amp; Consul.
[**adminAPIUpdateAction**](AdminAPIApi.md#adminAPIUpdateAction) | **PUT** /v1/actions | UpdateAction creates the action entity in AuthZ policy store.
[**adminAPIUpdatePermission**](AdminAPIApi.md#adminAPIUpdatePermission) | **PUT** /v1/permissions | UpdatePermission creates the permission entity in AuthZ policy store.
[**adminAPIUpdatePolicy**](AdminAPIApi.md#adminAPIUpdatePolicy) | **PUT** /v1/policies | UpdatePolicy creates the policy entity in AuthZ policy store.
[**adminAPIUpdatePrivilege**](AdminAPIApi.md#adminAPIUpdatePrivilege) | **PUT** /v1/privileges | UpdatePrivilege updates the privilege entity in AuthZ policy store.
[**adminAPIUpdatePrivilegeRoleMapping**](AdminAPIApi.md#adminAPIUpdatePrivilegeRoleMapping) | **PUT** /v1/privilege_role_mappings | UpdatePrivilegeRoleMapping updates the privilege role mapping in AuthZ policy store.
[**adminAPIUpdateResource**](AdminAPIApi.md#adminAPIUpdateResource) | **PUT** /v1/resources | UpdateResource creates the resource entity in AuthZ policy store.
[**adminAPIUpdateResourceGroup**](AdminAPIApi.md#adminAPIUpdateResourceGroup) | **PUT** /v1/resource_groups | UpdateResourceGroup updates the given resource group entity.
[**adminAPIUpdateRole**](AdminAPIApi.md#adminAPIUpdateRole) | **PUT** /v1/roles | UpdateRole creates the role entity in AuthZ policy store.
[**adminAPIUpdateService**](AdminAPIApi.md#adminAPIUpdateService) | **PUT** /v1/services | UpdateService creates the service entity in AuthZ policy store.
[**adminAPIUpdateSubjectRoleMapping**](AdminAPIApi.md#adminAPIUpdateSubjectRoleMapping) | **PUT** /v1/subject_role_mappings | UpdateSubjectRoleMapping updates the roles attached to a subject entity in AuthZ policy store.


# **adminAPICreateAction**
> \AuthzAdmin\Client\Model\V1Action adminAPICreateAction($body)

CreateAction creates the action entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Action(); // \AuthzAdmin\Client\Model\V1Action | 

try {
    $result = $apiInstance->adminAPICreateAction($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateAction: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Action**](../Model/V1Action.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Action**](../Model/V1Action.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreatePermission**
> \AuthzAdmin\Client\Model\V1Permission adminAPICreatePermission($body)

CreatePermission creates the permission entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Permission(); // \AuthzAdmin\Client\Model\V1Permission | 

try {
    $result = $apiInstance->adminAPICreatePermission($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreatePermission: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Permission**](../Model/V1Permission.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Permission**](../Model/V1Permission.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreatePolicy**
> \AuthzAdmin\Client\Model\V1Policy adminAPICreatePolicy($body)

CreatePolicy creates the policy entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Policy(); // \AuthzAdmin\Client\Model\V1Policy | 

try {
    $result = $apiInstance->adminAPICreatePolicy($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreatePolicy: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Policy**](../Model/V1Policy.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Policy**](../Model/V1Policy.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreatePrivilege**
> \AuthzAdmin\Client\Model\V1Privilege adminAPICreatePrivilege($body)

CreatePrivilege creates the privilege entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Privilege(); // \AuthzAdmin\Client\Model\V1Privilege | 

try {
    $result = $apiInstance->adminAPICreatePrivilege($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreatePrivilege: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Privilege**](../Model/V1Privilege.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Privilege**](../Model/V1Privilege.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreatePrivilegeRoleMapping**
> \AuthzAdmin\Client\Model\V1PrivilegeRoleMapping adminAPICreatePrivilegeRoleMapping($body)

CreatePrivilegeRoleMapping creates the privilege role mapping in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1PrivilegeRoleMapping(); // \AuthzAdmin\Client\Model\V1PrivilegeRoleMapping | 

try {
    $result = $apiInstance->adminAPICreatePrivilegeRoleMapping($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreatePrivilegeRoleMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1PrivilegeRoleMapping**](../Model/V1PrivilegeRoleMapping.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1PrivilegeRoleMapping**](../Model/V1PrivilegeRoleMapping.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreateResource**
> \AuthzAdmin\Client\Model\V1Resource adminAPICreateResource($body)

CreateResource creates the resource entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Resource(); // \AuthzAdmin\Client\Model\V1Resource | 

try {
    $result = $apiInstance->adminAPICreateResource($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateResource: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Resource**](../Model/V1Resource.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Resource**](../Model/V1Resource.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreateResourceGroup**
> \AuthzAdmin\Client\Model\V1ResourceGroup adminAPICreateResourceGroup($body)

CreateResourceGroup creates the resource group entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1ResourceGroup(); // \AuthzAdmin\Client\Model\V1ResourceGroup | 

try {
    $result = $apiInstance->adminAPICreateResourceGroup($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateResourceGroup: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1ResourceGroup**](../Model/V1ResourceGroup.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1ResourceGroup**](../Model/V1ResourceGroup.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreateResourceGroupMapping**
> \AuthzAdmin\Client\Model\V1ResourceGroupMapping adminAPICreateResourceGroupMapping($body)

CreateResourceGroupMapping creates the resource group mapping in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1ResourceGroupMapping(); // \AuthzAdmin\Client\Model\V1ResourceGroupMapping | 

try {
    $result = $apiInstance->adminAPICreateResourceGroupMapping($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateResourceGroupMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1ResourceGroupMapping**](../Model/V1ResourceGroupMapping.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1ResourceGroupMapping**](../Model/V1ResourceGroupMapping.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreateRole**
> \AuthzAdmin\Client\Model\V1Role adminAPICreateRole($body, $x_passport_jwt_v1)

CreateRole creates the role entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Role(); // \AuthzAdmin\Client\Model\V1Role | 
$x_passport_jwt_v1 = "x_passport_jwt_v1_example"; // string | 

try {
    $result = $apiInstance->adminAPICreateRole($body, $x_passport_jwt_v1);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateRole: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Role**](../Model/V1Role.md)|  |
 **x_passport_jwt_v1** | **string**|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Role**](../Model/V1Role.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreateRolePolicyMapping**
> \AuthzAdmin\Client\Model\V1RolePolicyMapping adminAPICreateRolePolicyMapping($body)

CreateRolePolicyMapping adds a policy to the given role in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1RolePolicyMapping(); // \AuthzAdmin\Client\Model\V1RolePolicyMapping | 

try {
    $result = $apiInstance->adminAPICreateRolePolicyMapping($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateRolePolicyMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1RolePolicyMapping**](../Model/V1RolePolicyMapping.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1RolePolicyMapping**](../Model/V1RolePolicyMapping.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreateService**
> \AuthzAdmin\Client\Model\V1Service adminAPICreateService($body)

CreateService creates the service entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Service(); // \AuthzAdmin\Client\Model\V1Service | 

try {
    $result = $apiInstance->adminAPICreateService($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateService: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Service**](../Model/V1Service.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Service**](../Model/V1Service.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPICreateSubjectRoleMapping**
> \AuthzAdmin\Client\Model\V1Null adminAPICreateSubjectRoleMapping($body)

CreateSubjectRoleMapping assigns the role to a subject entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1SubjectRoleMapping(); // \AuthzAdmin\Client\Model\V1SubjectRoleMapping | 

try {
    $result = $apiInstance->adminAPICreateSubjectRoleMapping($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPICreateSubjectRoleMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1SubjectRoleMapping**](../Model/V1SubjectRoleMapping.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteAction**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteAction($id, $name, $type)

DeleteAction deletes the action entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = "id_example"; // string | 
$name = "name_example"; // string | 
$type = "ACTION_TYPE_C"; // string | 

try {
    $result = $apiInstance->adminAPIDeleteAction($id, $name, $type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteAction: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |
 **name** | **string**|  | [optional]
 **type** | **string**|  | [optional] [default to ACTION_TYPE_C]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeletePermission**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeletePermission($id, $resource_id, $action_id, $effect, $group)

DeletePermission deletes the permission entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = "id_example"; // string | 
$resource_id = "resource_id_example"; // string | 
$action_id = "action_id_example"; // string | 
$effect = "EFFECT_UNKNOWN"; // string | 
$group = "group_example"; // string | 

try {
    $result = $apiInstance->adminAPIDeletePermission($id, $resource_id, $action_id, $effect, $group);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeletePermission: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |
 **resource_id** | **string**|  | [optional]
 **action_id** | **string**|  | [optional]
 **effect** | **string**|  | [optional] [default to EFFECT_UNKNOWN]
 **group** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeletePolicy**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeletePolicy($id, $name, $origin_service_id, $permission_id, $type, $is_assignable, $is_active)

DeletePolicy deletes the policy entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = "id_example"; // string | 
$name = "name_example"; // string | 
$origin_service_id = "origin_service_id_example"; // string | 
$permission_id = "permission_id_example"; // string | 
$type = "ROLE_POLICY_TYPE_INTERNAL"; // string | 
$is_assignable = true; // bool | 
$is_active = true; // bool | 

try {
    $result = $apiInstance->adminAPIDeletePolicy($id, $name, $origin_service_id, $permission_id, $type, $is_assignable, $is_active);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeletePolicy: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |
 **name** | **string**|  | [optional]
 **origin_service_id** | **string**|  | [optional]
 **permission_id** | **string**|  | [optional]
 **type** | **string**|  | [optional] [default to ROLE_POLICY_TYPE_INTERNAL]
 **is_assignable** | **bool**|  | [optional]
 **is_active** | **bool**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteResource**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteResource($id, $name)

DeleteResource deletes the resource entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = "id_example"; // string | 
$name = "name_example"; // string | 

try {
    $result = $apiInstance->adminAPIDeleteResource($id, $name);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteResource: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |
 **name** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteResourceGroup**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteResourceGroup($id, $name)

DeleteResourceGroup deletes the resource group entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = "id_example"; // string | 
$name = "name_example"; // string | 

try {
    $result = $apiInstance->adminAPIDeleteResourceGroup($id, $name);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteResourceGroup: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |
 **name** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteResourceGroupMapping**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteResourceGroupMapping($resource_id, $group_id)

DeleteResourceGroupMapping deletes the resource group mapping from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$resource_id = "resource_id_example"; // string | 
$group_id = "group_id_example"; // string | 

try {
    $result = $apiInstance->adminAPIDeleteResourceGroupMapping($resource_id, $group_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteResourceGroupMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **resource_id** | **string**|  | [optional]
 **group_id** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteRole**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteRole($id, $x_passport_jwt_v1, $name, $org_id, $type, $owner_type, $owner_id, $child_ids, $created_by, $description)

DeleteRole deletes the role entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = "id_example"; // string | 
$x_passport_jwt_v1 = "x_passport_jwt_v1_example"; // string | 
$name = "name_example"; // string | 
$org_id = "org_id_example"; // string | 
$type = "ROLE_POLICY_TYPE_INTERNAL"; // string | 
$owner_type = "owner_type_example"; // string | 
$owner_id = "owner_id_example"; // string | 
$child_ids = array("child_ids_example"); // string[] | 
$created_by = "created_by_example"; // string | 
$description = "description_example"; // string | 

try {
    $result = $apiInstance->adminAPIDeleteRole($id, $x_passport_jwt_v1, $name, $org_id, $type, $owner_type, $owner_id, $child_ids, $created_by, $description);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteRole: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |
 **x_passport_jwt_v1** | **string**|  |
 **name** | **string**|  | [optional]
 **org_id** | **string**|  | [optional]
 **type** | **string**|  | [optional] [default to ROLE_POLICY_TYPE_INTERNAL]
 **owner_type** | **string**|  | [optional]
 **owner_id** | **string**|  | [optional]
 **child_ids** | [**string[]**](../Model/string.md)|  | [optional]
 **created_by** | **string**|  | [optional]
 **description** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteRolePolicyMapping**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteRolePolicyMapping($role_id, $policy_id)

DeleteRolePolicyMapping deletes a policy from the given role in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$role_id = "role_id_example"; // string | 
$policy_id = "policy_id_example"; // string | 

try {
    $result = $apiInstance->adminAPIDeleteRolePolicyMapping($role_id, $policy_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteRolePolicyMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **role_id** | **string**|  | [optional]
 **policy_id** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteService**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteService($id, $name)

DeleteService deletes the service entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = "id_example"; // string | 
$name = "name_example"; // string | 

try {
    $result = $apiInstance->adminAPIDeleteService($id, $name);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteService: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |
 **name** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteSubject**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteSubject($role_id, $key_id, $key_owner_type, $key_owner_id, $role_names)

DeleteSubject detaches all roles for the given subject entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$role_id = array("role_id_example"); // string[] | 
$key_id = "key_id_example"; // string | 
$key_owner_type = "key_owner_type_example"; // string | 
$key_owner_id = "key_owner_id_example"; // string | 
$role_names = array("role_names_example"); // string[] | 

try {
    $result = $apiInstance->adminAPIDeleteSubject($role_id, $key_id, $key_owner_type, $key_owner_id, $role_names);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteSubject: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **role_id** | [**string[]**](../Model/string.md)|  | [optional]
 **key_id** | **string**|  | [optional]
 **key_owner_type** | **string**|  | [optional]
 **key_owner_id** | **string**|  | [optional]
 **role_names** | [**string[]**](../Model/string.md)|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIDeleteSubjectRoleMapping**
> \AuthzAdmin\Client\Model\V1Null adminAPIDeleteSubjectRoleMapping($role_id, $key_id, $key_owner_type, $key_owner_id, $role_names)

DeleteSubjectRoleMapping detaches the role from a subject entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$role_id = array("role_id_example"); // string[] | 
$key_id = "key_id_example"; // string | 
$key_owner_type = "key_owner_type_example"; // string | 
$key_owner_id = "key_owner_id_example"; // string | 
$role_names = array("role_names_example"); // string[] | 

try {
    $result = $apiInstance->adminAPIDeleteSubjectRoleMapping($role_id, $key_id, $key_owner_type, $key_owner_id, $role_names);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIDeleteSubjectRoleMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **role_id** | [**string[]**](../Model/string.md)|  | [optional]
 **key_id** | **string**|  | [optional]
 **key_owner_type** | **string**|  | [optional]
 **key_owner_id** | **string**|  | [optional]
 **role_names** | [**string[]**](../Model/string.md)|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIGetRole**
> \AuthzAdmin\Client\Model\V1Role adminAPIGetRole($identifier, $org_id, $owner_id, $expand_children)

GetRole returns the role entity from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$identifier = "identifier_example"; // string | 
$org_id = "org_id_example"; // string | 
$owner_id = "owner_id_example"; // string | 
$expand_children = true; // bool | 

try {
    $result = $apiInstance->adminAPIGetRole($identifier, $org_id, $owner_id, $expand_children);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIGetRole: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **identifier** | **string**|  |
 **org_id** | **string**|  | [optional]
 **owner_id** | **string**|  | [optional]
 **expand_children** | **bool**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1Role**](../Model/V1Role.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListAction**
> \AuthzAdmin\Client\Model\V1ListActionResponse adminAPIListAction($pagination_token, $action_name_prefix)

ListAction returns a list of actions based on the supplied filters.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$pagination_token = "pagination_token_example"; // string | 
$action_name_prefix = "action_name_prefix_example"; // string | 

try {
    $result = $apiInstance->adminAPIListAction($pagination_token, $action_name_prefix);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListAction: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **pagination_token** | **string**|  | [optional]
 **action_name_prefix** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListActionResponse**](../Model/V1ListActionResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListPermission**
> \AuthzAdmin\Client\Model\V1ListPermissionResponse adminAPIListPermission($pagination_token, $resource_group_id_list, $resource_id_list, $action_id_list)

ListPermission returns a list of permissions satisfying the filter conditions.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$pagination_token = "pagination_token_example"; // string | 
$resource_group_id_list = array("resource_group_id_list_example"); // string[] | 
$resource_id_list = array("resource_id_list_example"); // string[] | 
$action_id_list = array("action_id_list_example"); // string[] | 

try {
    $result = $apiInstance->adminAPIListPermission($pagination_token, $resource_group_id_list, $resource_id_list, $action_id_list);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListPermission: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **pagination_token** | **string**|  | [optional]
 **resource_group_id_list** | [**string[]**](../Model/string.md)|  | [optional]
 **resource_id_list** | [**string[]**](../Model/string.md)|  | [optional]
 **action_id_list** | [**string[]**](../Model/string.md)|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListPermissionResponse**](../Model/V1ListPermissionResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListPolicy**
> \AuthzAdmin\Client\Model\V1ListPolicyResponse adminAPIListPolicy($pagination_token, $resource_group_id_list, $resource_id_list, $role_id, $service_id_list, $permission_id_list, $role_names, $org_id, $role_owner_id, $enrich_roles, $role_ids, $role_identifier, $org_ids)

ListPolicy returns a list of policies satisfying the filter conditions.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$pagination_token = "pagination_token_example"; // string | 
$resource_group_id_list = array("resource_group_id_list_example"); // string[] | 
$resource_id_list = array("resource_id_list_example"); // string[] | 
$role_id = "role_id_example"; // string | DEPRECATED: use role_names and org_id instead.
$service_id_list = array("service_id_list_example"); // string[] | 
$permission_id_list = array("permission_id_list_example"); // string[] | 
$role_names = array("role_names_example"); // string[] | 
$org_id = "org_id_example"; // string | 
$role_owner_id = "role_owner_id_example"; // string | 
$enrich_roles = true; // bool | 
$role_ids = array("role_ids_example"); // string[] | 
$role_identifier = "role_identifier_example"; // string | 
$org_ids = array("org_ids_example"); // string[] | 

try {
    $result = $apiInstance->adminAPIListPolicy($pagination_token, $resource_group_id_list, $resource_id_list, $role_id, $service_id_list, $permission_id_list, $role_names, $org_id, $role_owner_id, $enrich_roles, $role_ids, $role_identifier, $org_ids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListPolicy: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **pagination_token** | **string**|  | [optional]
 **resource_group_id_list** | [**string[]**](../Model/string.md)|  | [optional]
 **resource_id_list** | [**string[]**](../Model/string.md)|  | [optional]
 **role_id** | **string**| DEPRECATED: use role_names and org_id instead. | [optional]
 **service_id_list** | [**string[]**](../Model/string.md)|  | [optional]
 **permission_id_list** | [**string[]**](../Model/string.md)|  | [optional]
 **role_names** | [**string[]**](../Model/string.md)|  | [optional]
 **org_id** | **string**|  | [optional]
 **role_owner_id** | **string**|  | [optional]
 **enrich_roles** | **bool**|  | [optional]
 **role_ids** | [**string[]**](../Model/string.md)|  | [optional]
 **role_identifier** | **string**|  | [optional]
 **org_ids** | [**string[]**](../Model/string.md)|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListPolicyResponse**](../Model/V1ListPolicyResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListPrivileges**
> \AuthzAdmin\Client\Model\V1ListPrivilegesResponse adminAPIListPrivileges($org_id, $expand_actions, $expand_roles, $pagination_token, $visibility)

ListPrivileges return a list of privileges for the given orgId

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$org_id = "org_id_example"; // string | 
$expand_actions = true; // bool | 
$expand_roles = true; // bool | 
$pagination_token = "pagination_token_example"; // string | 
$visibility = 56; // int | 

try {
    $result = $apiInstance->adminAPIListPrivileges($org_id, $expand_actions, $expand_roles, $pagination_token, $visibility);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListPrivileges: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **org_id** | **string**|  | [optional]
 **expand_actions** | **bool**|  | [optional]
 **expand_roles** | **bool**|  | [optional]
 **pagination_token** | **string**|  | [optional]
 **visibility** | **int**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListPrivilegesResponse**](../Model/V1ListPrivilegesResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListResource**
> \AuthzAdmin\Client\Model\V1ListResourceResponse adminAPIListResource($pagination_token, $resource_group_id, $resource_group_name_prefix, $resource_name_prefix)

ListResource returns a list of resources based on the supplied filters.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$pagination_token = "pagination_token_example"; // string | 
$resource_group_id = "resource_group_id_example"; // string | 
$resource_group_name_prefix = "resource_group_name_prefix_example"; // string | 
$resource_name_prefix = "resource_name_prefix_example"; // string | 

try {
    $result = $apiInstance->adminAPIListResource($pagination_token, $resource_group_id, $resource_group_name_prefix, $resource_name_prefix);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListResource: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **pagination_token** | **string**|  | [optional]
 **resource_group_id** | **string**|  | [optional]
 **resource_group_name_prefix** | **string**|  | [optional]
 **resource_name_prefix** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListResourceResponse**](../Model/V1ListResourceResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListResourceGroup**
> \AuthzAdmin\Client\Model\V1ListResourceGroupResponse adminAPIListResourceGroup($pagination_token, $resource_group_name_prefix)

ListResourceGroup returns a list of resource group entities from AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$pagination_token = "pagination_token_example"; // string | 
$resource_group_name_prefix = "resource_group_name_prefix_example"; // string | 

try {
    $result = $apiInstance->adminAPIListResourceGroup($pagination_token, $resource_group_name_prefix);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListResourceGroup: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **pagination_token** | **string**|  | [optional]
 **resource_group_name_prefix** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListResourceGroupResponse**](../Model/V1ListResourceGroupResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListRole**
> \AuthzAdmin\Client\Model\V1ListRoleResponse adminAPIListRole($pagination_token, $role_name_prefix, $role_names, $role_ids, $org_id, $key_id, $key_owner_type, $key_owner_id, $owner_ids, $type, $types)

ListRole returns a list of roles matching the filter condition.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$pagination_token = "pagination_token_example"; // string | 
$role_name_prefix = "role_name_prefix_example"; // string | 
$role_names = array("role_names_example"); // string[] | 
$role_ids = array("role_ids_example"); // string[] | 
$org_id = "org_id_example"; // string | 
$key_id = "key_id_example"; // string | 
$key_owner_type = "key_owner_type_example"; // string | 
$key_owner_id = "key_owner_id_example"; // string | 
$owner_ids = array("owner_ids_example"); // string[] | 
$type = "type_example"; // string | 
$types = array("types_example"); // string[] | 

try {
    $result = $apiInstance->adminAPIListRole($pagination_token, $role_name_prefix, $role_names, $role_ids, $org_id, $key_id, $key_owner_type, $key_owner_id, $owner_ids, $type, $types);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListRole: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **pagination_token** | **string**|  | [optional]
 **role_name_prefix** | **string**|  | [optional]
 **role_names** | [**string[]**](../Model/string.md)|  | [optional]
 **role_ids** | [**string[]**](../Model/string.md)|  | [optional]
 **org_id** | **string**|  | [optional]
 **key_id** | **string**|  | [optional]
 **key_owner_type** | **string**|  | [optional]
 **key_owner_id** | **string**|  | [optional]
 **owner_ids** | [**string[]**](../Model/string.md)|  | [optional]
 **type** | **string**|  | [optional]
 **types** | [**string[]**](../Model/string.md)|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListRoleResponse**](../Model/V1ListRoleResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIListService**
> \AuthzAdmin\Client\Model\V1ListServiceResponse adminAPIListService($pagination_token, $service_name_prefix)

ListService returns a list of services based on the supplied filters.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$pagination_token = "pagination_token_example"; // string | 
$service_name_prefix = "service_name_prefix_example"; // string | 

try {
    $result = $apiInstance->adminAPIListService($pagination_token, $service_name_prefix);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIListService: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **pagination_token** | **string**|  | [optional]
 **service_name_prefix** | **string**|  | [optional]

### Return type

[**\AuthzAdmin\Client\Model\V1ListServiceResponse**](../Model/V1ListServiceResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIMigrateRole**
> \AuthzAdmin\Client\Model\V1MigrateRoleResponse adminAPIMigrateRole($body)

MigrateRole create role entities with pre-defined IDs. This will be decommissioned after CAC migration

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1MigrateRoleRequest(); // \AuthzAdmin\Client\Model\V1MigrateRoleRequest | 

try {
    $result = $apiInstance->adminAPIMigrateRole($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIMigrateRole: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1MigrateRoleRequest**](../Model/V1MigrateRoleRequest.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1MigrateRoleResponse**](../Model/V1MigrateRoleResponse.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIRecon**
> \AuthzAdmin\Client\Model\V1Null adminAPIRecon($body)

Recon is to be used for reconciliation of policies between MySQL & Consul.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Null(); // \AuthzAdmin\Client\Model\V1Null | Null defines an empty message which the service uses when it doesn't have any data to return.

try {
    $result = $apiInstance->adminAPIRecon($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIRecon: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)| Null defines an empty message which the service uses when it doesn&#39;t have any data to return. |

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdateAction**
> \AuthzAdmin\Client\Model\V1Action adminAPIUpdateAction($body)

UpdateAction creates the action entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Action(); // \AuthzAdmin\Client\Model\V1Action | 

try {
    $result = $apiInstance->adminAPIUpdateAction($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdateAction: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Action**](../Model/V1Action.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Action**](../Model/V1Action.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdatePermission**
> \AuthzAdmin\Client\Model\V1Permission adminAPIUpdatePermission($body)

UpdatePermission creates the permission entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Permission(); // \AuthzAdmin\Client\Model\V1Permission | 

try {
    $result = $apiInstance->adminAPIUpdatePermission($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdatePermission: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Permission**](../Model/V1Permission.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Permission**](../Model/V1Permission.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdatePolicy**
> \AuthzAdmin\Client\Model\V1Policy adminAPIUpdatePolicy($body)

UpdatePolicy creates the policy entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Policy(); // \AuthzAdmin\Client\Model\V1Policy | 

try {
    $result = $apiInstance->adminAPIUpdatePolicy($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdatePolicy: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Policy**](../Model/V1Policy.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Policy**](../Model/V1Policy.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdatePrivilege**
> \AuthzAdmin\Client\Model\V1Privilege adminAPIUpdatePrivilege($body)

UpdatePrivilege updates the privilege entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Privilege(); // \AuthzAdmin\Client\Model\V1Privilege | 

try {
    $result = $apiInstance->adminAPIUpdatePrivilege($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdatePrivilege: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Privilege**](../Model/V1Privilege.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Privilege**](../Model/V1Privilege.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdatePrivilegeRoleMapping**
> \AuthzAdmin\Client\Model\V1PrivilegeRoleMapping adminAPIUpdatePrivilegeRoleMapping($body)

UpdatePrivilegeRoleMapping updates the privilege role mapping in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1PrivilegeRoleMapping(); // \AuthzAdmin\Client\Model\V1PrivilegeRoleMapping | 

try {
    $result = $apiInstance->adminAPIUpdatePrivilegeRoleMapping($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdatePrivilegeRoleMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1PrivilegeRoleMapping**](../Model/V1PrivilegeRoleMapping.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1PrivilegeRoleMapping**](../Model/V1PrivilegeRoleMapping.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdateResource**
> \AuthzAdmin\Client\Model\V1Resource adminAPIUpdateResource($body)

UpdateResource creates the resource entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Resource(); // \AuthzAdmin\Client\Model\V1Resource | 

try {
    $result = $apiInstance->adminAPIUpdateResource($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdateResource: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Resource**](../Model/V1Resource.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Resource**](../Model/V1Resource.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdateResourceGroup**
> \AuthzAdmin\Client\Model\V1ResourceGroup adminAPIUpdateResourceGroup($body)

UpdateResourceGroup updates the given resource group entity.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1ResourceGroup(); // \AuthzAdmin\Client\Model\V1ResourceGroup | 

try {
    $result = $apiInstance->adminAPIUpdateResourceGroup($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdateResourceGroup: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1ResourceGroup**](../Model/V1ResourceGroup.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1ResourceGroup**](../Model/V1ResourceGroup.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdateRole**
> \AuthzAdmin\Client\Model\V1Role adminAPIUpdateRole($body, $x_passport_jwt_v1)

UpdateRole creates the role entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Role(); // \AuthzAdmin\Client\Model\V1Role | 
$x_passport_jwt_v1 = "x_passport_jwt_v1_example"; // string | 

try {
    $result = $apiInstance->adminAPIUpdateRole($body, $x_passport_jwt_v1);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdateRole: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Role**](../Model/V1Role.md)|  |
 **x_passport_jwt_v1** | **string**|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Role**](../Model/V1Role.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdateService**
> \AuthzAdmin\Client\Model\V1Service adminAPIUpdateService($body)

UpdateService creates the service entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1Service(); // \AuthzAdmin\Client\Model\V1Service | 

try {
    $result = $apiInstance->adminAPIUpdateService($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdateService: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1Service**](../Model/V1Service.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Service**](../Model/V1Service.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **adminAPIUpdateSubjectRoleMapping**
> \AuthzAdmin\Client\Model\V1Null adminAPIUpdateSubjectRoleMapping($body)

UpdateSubjectRoleMapping updates the roles attached to a subject entity in AuthZ policy store.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new AuthzAdmin\Client\Api\AdminAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$body = new \AuthzAdmin\Client\Model\V1SubjectRoleMapping(); // \AuthzAdmin\Client\Model\V1SubjectRoleMapping | 

try {
    $result = $apiInstance->adminAPIUpdateSubjectRoleMapping($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AdminAPIApi->adminAPIUpdateSubjectRoleMapping: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\AuthzAdmin\Client\Model\V1SubjectRoleMapping**](../Model/V1SubjectRoleMapping.md)|  |

### Return type

[**\AuthzAdmin\Client\Model\V1Null**](../Model/V1Null.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

