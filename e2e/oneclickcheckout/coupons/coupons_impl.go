package coupons

import (
	"net/http"
	"testing"

	"github.com/razorpay/api/e2e"
)

func FetchCoupons(t *testing.T, couponsReq FetchCouponsRequest) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.POST("/v1/merchant/coupons").
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithHeaders(header).
		WithQueryObject(query).
		WithJSON(couponsReq).
		Expect().
		Status(http.StatusOK).Body()
	return []byte(obj.Raw())
}

func ApplyCoupons(t *testing.T, couponsReq FetchCouponsRequest) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.POST("/v1/merchant/coupon/apply").
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithHeaders(header).
		WithQueryObject(query).
		WithJSON(couponsReq).
		Expect().
		Status(http.StatusOK).Body()
	return []byte(obj.Raw())
}

func RemoveCoupon(t *testing.T, removeCouponsReq RemoveCouponRequest) {
	Initialize(t)
	oneClickCheckoutHost.POST("/v1/merchant/coupon/remove").
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithHeaders(header).
		WithJSON(removeCouponsReq).
		Expect().
		Status(http.StatusOK).Body()
}
