package impl

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/api/e2e/common/dtos"
	linked_account_activation "github.com/razorpay/api/e2e/linkedaccountactivation"
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
	"testing"
)

func FetchMerchantActivationDetails(t *testing.T, accountId string) linked_account_activation.MerchantActivationDetails {
	var res linked_account_activation.MerchantActivationDetails
	obj := httpexpect.New(t, e2e.Config.App.Hostname).GET(e2e.MERCHANT_FETCH_ACTIVATION_DETAILS).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithHeaders(map[string]string{
			"X-Razorpay-Account":    accountId,
			"X-Dashboard-User-id":   e2e.Config.OnboardingAPIsPartner.User,
			"X-Dashboard-User-Role": e2e.Config.OnboardingAPIsPartner.Role,
		}).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &res)
	return res
}

func UpdateMerchantActivationStatus(t *testing.T, merchantId string, activationStatusRequest dtos.ActivationStatusRequest) dtos.ActivationStatusResponse {
	var res dtos.ActivationStatusResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).PATCH(e2e.MERCHANT_UPDATE_STATUS, merchantId).
		WithBasicAuth(e2e.Config.AdminConfig.Username, e2e.Config.AdminConfig.Password).
		WithHeader("X-Org-Id", e2e.Config.AdminConfig.OrgId).
		WithHeader("X-Admin-Token", e2e.Config.AdminConfig.Token).
		WithJSON(activationStatusRequest).
		Expect().
		Status(http.StatusOK).Body()

	json.Unmarshal([]byte(obj.Raw()), &res)
	return res
}

func SendMockBvsValidationEvent(t *testing.T, validationData linked_account_activation.MockBVSValidationEventRequest) {
	httpexpect.New(t, e2e.Config.App.Hostname).POST("/v1/mock-bvs-validation").
		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
		WithJSON(validationData).
		WithHeaders(map[string]string{
			"X-Dashboard-User-id":   e2e.Config.SubMerchant.User,
			"X-Dashboard-User-Role": e2e.Config.SubMerchant.Role,
			"Content-Type":          "application/json",
		}).
		Expect().
		Status(http.StatusOK)
}
