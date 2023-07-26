package e2e

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	exp "github.com/razorpay/api/e2e/partner-experience"
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
	"testing"
)

func UpdatePartnerType(t *testing.T, request UpdatePartnerRequest) UpdatePartnerResponse {
	var updatePartnerResponse UpdatePartnerResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).PATCH(exp.UpdatePartnerTypeRoute).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(request).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &updatePartnerResponse)
	return updatePartnerResponse
}
