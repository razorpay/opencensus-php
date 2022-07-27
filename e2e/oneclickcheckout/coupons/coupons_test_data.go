package coupons

var (
	TestCustomerContact = "+918951468289"
	TestCustomerEmail   = "sangeeta.ng@razorpay.com"
	TestCouponCode      = "FLAT10"
)

type FetchCouponsTest struct {
	Name     string
	Request  *FetchCouponsRequest
	Response *FetchCouponsResponse
	Error    error
}

func GetFetchCouponsRequest(orderId string) *FetchCouponsRequest {
	return &FetchCouponsRequest{
		Contact: TestCustomerContact,
		Email:   TestCustomerEmail,
		OrderID: orderId,
	}
}

type ApplyCouponsTest struct {
	Name     string
	Request  *FetchCouponsRequest
	Response *ApplyCouponsResponse
	Error    error
}

func FetchApplyCouponsRequest(orderId string, code string) *FetchCouponsRequest {
	return &FetchCouponsRequest{
		Email:   TestCustomerEmail,
		Contact: TestCustomerContact,
		OrderID: orderId,
		Code:    code,
	}
}

func FetchApplyCouponsResponse(code string) *ApplyCouponsResponse {
	return &ApplyCouponsResponse{
		Promotions: []*Promotions{
			{
				Code: code,
			},
		},
	}
}
