package e2e

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
	"testing"
)
// Create PaymentPage
func CreatePaymentPage(t *testing.T, paymentPageReq PaymentPageRequest) PaymentPageResponse {
	var paymentPageRes PaymentPageResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).
		POST("/v1/payment_pages").
		WithBasicAuth(e2e.Config.PaymentPage.Username,e2e.Config.PaymentPage.Password).
		WithHeaders(map[string]string{
			"X-Dashboard-User-id": e2e.Config.PaymentPage.User,
			"X-Dashboard-User-Role": e2e.Config.PaymentPage.Role,
			"Content-Type": "application/json",
		}).
		WithJSON(paymentPageReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &paymentPageRes)
	return paymentPageRes
}
