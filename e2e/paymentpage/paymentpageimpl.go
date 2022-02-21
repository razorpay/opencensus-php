package e2e

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	"net/http"
	"testing"
)


// Create PaymentPage
func CreatePaymentPage(t *testing.T, paymentPageReq PaymentPageRequest) PaymentPageResponse {
	var paymentPageRes PaymentPageResponse
	Initialize(t)
	obj := paymentPageHost.POST("/v1/payment_pages").
		WithBasicAuth(e2e.Config.PaymentPage.Username, e2e.Config.PaymentPage.Password).
		WithHeaders(header).
		WithJSON(paymentPageReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &paymentPageRes)
	return paymentPageRes
}
