package e2e

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	exp "github.com/razorpay/api/e2e/partner-experience"
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
	"testing"
)

func CreateSubMerchant(t *testing.T, request SubMerchantCreateRequest) SubMerchantCreateResponse {
	var submerchantCreateResponse SubMerchantCreateResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).POST(exp.CreateSubMerchantRoute).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(request).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &submerchantCreateResponse)
	return submerchantCreateResponse
}

func GetSubMerchant(t *testing.T, mid string) SubMerchantCreateResponse {
	var submerchantCreateResponse SubMerchantCreateResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).GET(exp.GetSubMerchantRoute, mid).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &submerchantCreateResponse)
	return submerchantCreateResponse
}
