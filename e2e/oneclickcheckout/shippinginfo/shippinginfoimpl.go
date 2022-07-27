package shippinginfo

import (
	"testing"

	"github.com/razorpay/api/e2e"
)

func FetchShippingInfo(t *testing.T, shippingInfoReq ShippingInfoRequest) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.POST("/v1/merchant/shipping_info").
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithQueryObject(query).
		WithHeaders(header).
		WithJSON(shippingInfoReq).
		Expect().
		Body()
	return []byte(obj.Raw())
}

func CheckCodEligibility(t *testing.T, req CodEligibilityRequest) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.POST("/v1/1cc/check_cod_eligibility").
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithQueryObject(query).
		WithHeaders(header).
		WithJSON(req).
		Expect().
		Body()
	return []byte(obj.Raw())
}
