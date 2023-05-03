package e2e

const (
	ACCOUNTS_CREATE_V2                = "/v2/accounts"
	ACCOUNTS_FETCH_V2                 = "/v2/accounts/{id}"
	PRODUCT_CONFIG_CREATE_V2          = "/v2/accounts/{id}/products"
	PRODUCT_CONFIG_V2                 = "/v2/accounts/{id}/products/{product_config_id}"
	STAKEHOLDER_CREATE_V2             = "/v2/accounts/{id}/stakeholders"
	STAKEHOLDER_FETCH_V2              = "/v2/accounts/{id}/stakeholders/{stakeholder_id}"
	MERCHANT_UPDATE_STATUS            = "/v1/merchant/activation/{id}/activation_status"
	MERCHANT_FETCH_ACTIVATION_DETAILS = "/v1/merchant/activation"
)
