package loggedoutflow

import (
	"github.com/razorpay/api/e2e/oneclickcheckout/coupons"
	"github.com/razorpay/api/e2e/oneclickcheckout/order"
	"github.com/razorpay/api/e2e/oneclickcheckout/preference"
	"github.com/razorpay/api/e2e/oneclickcheckout/shippinginfo"
)

func (s *LoggedOutFlowTestSuite) TestCouponWithUnserviceable() {
	orderCreateTest := order.OrderCreateTest{
		Name:     "OrderCreate",
		Request:  order.OrderCreateSuccessRequest(),
		Response: order.OrderCreateSuccessResponse(),
	}
	orderCreateTest = order.TestOrderCreation(s.T(), orderCreateTest)

	getPreferenceTest := preference.GetPreferenceTest{
		Name:     "GetPreference",
		OrderId:  orderCreateTest.Response.ID,
		Response: preference.GetPreferenceResponse(),
	}
	preference.TestGetPreferences(s.T(), getPreferenceTest)

	updateCustomerDetailsTest := order.OrderUpdateTest{
		Name:    "UpdateContactDetails",
		OrderId: orderCreateTest.Response.ID,
		Request: order.UpdateCustomerContactDetailRequest(),
	}
	order.TestCustomerDetailUpdate(s.T(), updateCustomerDetailsTest)

	fetchOrderByIdTest := order.OrderFetchTest{
		Name:     "FetchOrder",
		OrderId:  orderCreateTest.Response.ID,
		Response: order.OrderUpdateCustomerContactDetailResponse(),
	}
	order.TestFetchOrderById(s.T(), fetchOrderByIdTest)

	fetchCouponsTest := coupons.FetchCouponsTest{
		Name:    "FetchCoupon",
		Request: coupons.GetFetchCouponsRequest(orderCreateTest.Response.ID),
	}
	fetchCouponsTest = coupons.TestFetchCoupons(s.T(), fetchCouponsTest)

	applyCouponsTest := coupons.ApplyCouponsTest{
		Name:     "ApplyCoupon",
		Request:  coupons.FetchApplyCouponsRequest(orderCreateTest.Response.ID, fetchCouponsTest.Response.Promotions[0].Code),
		Response: coupons.FetchApplyCouponsResponse(fetchCouponsTest.Response.Promotions[0].Code),
	}

	applyCouponsTest = coupons.TestApplyCoupons(s.T(), applyCouponsTest)

	shippingInfoTest := shippinginfo.FetchShippingInfoTest{
		Name:     "FetchShippingInfo",
		Request:  shippinginfo.FetchShippingInfoRequest(orderCreateTest.Response.ID, shippinginfo.TestUnserviceableZipcode),
		Response: shippinginfo.FetchShippingInfoResponse(shippinginfo.TestUnserviceableZipcode, false),
	}
	shippingInfoTest = shippinginfo.TestFetchShippingInfo(s.T(), shippingInfoTest)

	updateCustomerDetailsTest = order.OrderUpdateTest{
		Name:    "UpdateAddressDetails",
		OrderId: orderCreateTest.Response.ID,
		Request: order.UpdateCustomerAddressDetailRequest(shippinginfo.TestUnserviceableZipcode),
		Error:   shippinginfo.ErrorForShippingInfoNotFound(),
	}
	order.TestCustomerDetailUpdate(s.T(), updateCustomerDetailsTest)
}
