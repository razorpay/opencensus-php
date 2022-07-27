package coupons

import (
	"encoding/json"
	"testing"

	"github.com/razorpay/api/e2e/oneclickcheckout"
)

func TestFetchCoupons(t *testing.T, fetchCouponsTest FetchCouponsTest) FetchCouponsTest {
	fetchCouponsRes := FetchCoupons(t, *fetchCouponsTest.Request)
	var actualRes FetchCouponsResponse
	json.Unmarshal(fetchCouponsRes, &actualRes)
	fetchCouponsTest.Response = &actualRes

	return fetchCouponsTest
}

func TestApplyCoupons(t *testing.T, applyCouponsTest ApplyCouponsTest) ApplyCouponsTest {
	applyCouponsRes := ApplyCoupons(t, *applyCouponsTest.Request)
	var actualRes ApplyCouponsResponse
	if applyCouponsTest.Response != nil {
		expected, _ := json.Marshal(applyCouponsTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, applyCouponsRes)
		json.Unmarshal(applyCouponsRes, &actualRes)
		applyCouponsTest.Response = &actualRes
	}

	return applyCouponsTest
}
