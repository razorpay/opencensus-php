package shippinginfo

import "github.com/razorpay/api/e2e/oneclickcheckout"

var (
	TestAccountNumber        = "040304030403040"
	TestCountry              = "in"
	TestServiceableZipcode   = "560001"
	TestUnserviceableZipcode = "180006"
)

type FetchShippingInfoTest struct {
	Name     string
	Request  *ShippingInfoRequest
	Response *ShippingInfoResponse
	Error    error
}

type CodEligibilityTest struct {
	Name     string
	Request  *CodEligibilityRequest
	Response *CodEligibilityResponse
	Error    error
}

func FetchShippingInfoRequest(orderId string, zipcode string) *ShippingInfoRequest {
	return &ShippingInfoRequest{
		OrderId: orderId,
		Address: []*Addresses{
			{
				Zipcode: zipcode,
				Country: TestCountry,
			},
		},
	}
}

func FetchShippingInfoResponse(zipcode string, serviceable bool) *ShippingInfoResponse {
	return &ShippingInfoResponse{
		Address: []*Addresses{
			{
				Zipcode:     zipcode,
				Country:     TestCountry,
				Serviceable: serviceable,
			},
		},
	}
}

func CODEligibilityRequest(orderId string) *CodEligibilityRequest {
	return &CodEligibilityRequest{
		OrderId: orderId,
		Address: &Address{
			Zipcode:  "560001",
			Country:  "in",
			City:     "Bengaluru",
			State:    "Karnataka",
			Line1:    "123",
			Line2:    "Whitefield",
			Name:     "Dev tester",
			Tag:      "",
			Type:     "shipping_address",
			Contact:  "+918951468289",
			Landmark: "landmark",
		},
		Device: &Device{
			Id: "1.e9098ab530b296443ca1befcc38c0f1d417f5ff2.1648815531932.94845948",
		},
	}
}

func CODEligibilityResponse() *CodEligibilityResponse {
	return &CodEligibilityResponse{
		Cod: true,
	}
}

func ErrorForShippingInfoNotFound() *oneclickcheckout.ErrorResponse {
	return &oneclickcheckout.ErrorResponse{
		Error: &oneclickcheckout.Error{
			Code:        "BAD_REQUEST_ERROR",
			Description: "Shipping Info not found",
			Source:      "NA",
			Step:        "NA",
			Reason:      "NA",
			Metadata:    struct{}{},
		},
	}
}
