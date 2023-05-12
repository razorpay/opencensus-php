package e2e

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/api/e2e/partnerships/dtos"
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
	"testing"
)

func CreateProductConfig(t *testing.T, accountId string, productConfigRequest dtos.ProductConfigCreateRequest) dtos.ProductConfigResponse {
	var productConfigResponse dtos.ProductConfigResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).POST(e2e.PRODUCT_CONFIG_CREATE_V2, accountId).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(productConfigRequest).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &productConfigResponse)
	return productConfigResponse
}

func UpdateProductConfig(t *testing.T, accountId string, productConfigId string, productConfigRequest dtos.ProductConfigRequest) dtos.ProductConfigResponse {
	var productConfigResponse dtos.ProductConfigResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).PATCH(e2e.PRODUCT_CONFIG_V2, accountId, productConfigId).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(productConfigRequest).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &productConfigResponse)
	return productConfigResponse
}

func FetchProductConfig(t *testing.T, accountId string, productConfigId string) dtos.ProductConfigResponse {
	var productConfigResponse dtos.ProductConfigResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).GET(e2e.PRODUCT_CONFIG_V2, accountId, productConfigId).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &productConfigResponse)
	return productConfigResponse
}
