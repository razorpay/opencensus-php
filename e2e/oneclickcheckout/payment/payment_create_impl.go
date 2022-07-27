package payment

import (
	"testing"
)

func CreatePayment(t *testing.T, paymentReq CreatePaymentRequest) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.POST("/v1/payments/create/ajax").
		WithHeaders(header).
		WithJSON(paymentReq).
		Expect().
		Body()
	return []byte(obj.Raw())
}
