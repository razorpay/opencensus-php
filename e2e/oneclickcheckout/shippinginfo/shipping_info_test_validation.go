package shippinginfo

import (
	"encoding/json"
	"testing"

	"github.com/razorpay/api/e2e/oneclickcheckout"
)

func TestFetchShippingInfo(t *testing.T, fetchShippingInfoTest FetchShippingInfoTest) FetchShippingInfoTest {
	fetchShippingInfoRes := FetchShippingInfo(t, *fetchShippingInfoTest.Request)
	var actualRes ShippingInfoResponse
	if fetchShippingInfoTest.Response != nil {
		expected, _ := json.Marshal(fetchShippingInfoTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, fetchShippingInfoRes)
		json.Unmarshal(fetchShippingInfoRes, &actualRes)
		fetchShippingInfoTest.Response = &actualRes
	}
	return fetchShippingInfoTest
}

func TestCODEligibility(t *testing.T, codEligibilityTest CodEligibilityTest) CodEligibilityTest {
	codEligibilityRes := CheckCodEligibility(t, *codEligibilityTest.Request)
	var actualRes CodEligibilityResponse
	if codEligibilityTest.Response != nil {
		expected, _ := json.Marshal(codEligibilityTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, codEligibilityRes)
		json.Unmarshal(codEligibilityRes, &actualRes)
		codEligibilityTest.Response = &actualRes
	}
	return codEligibilityTest
}
