package testdata

import "github.com/razorpay/api/e2e/partnerships/dtos"

type StakeholderTestCase struct {
	Description string
	Req         dtos.StakeholderRequest
}

var StakeholderTestCases = map[string]StakeholderTestCase{
	"All_Stakeholder_Fields": {
		Description: "Create stakeholder request with all fields",
		Req: dtos.StakeholderRequest{
			PercentageOwnership: 40,
			Name:                "Rzp Test QA Merchant",
			Email:               "rahul@acme.com",
			Relationship: &dtos.Relationship{
				Director:  false,
				Executive: true,
			},
			Phone: &dtos.Phone{
				Primary:   "7074757474",
				Secondary: "7074757474",
			},
			Addresses: &dtos.StakeholderAddresses{
				Residential: &dtos.Residential{
					Street:     "507, Koramangala 2nd block",
					City:       "Bangalore",
					State:      "Karnataka",
					PostalCode: "560035",
					Country:    "IN",
				},
			},
			Kyc: &dtos.Kyc{
				Pan: "ABCPD1234A",
			},
			Notes: map[string]interface{}{
				"random_key_by_partner": "random_value2",
			},
		},
	},
	"Update_Pan": {
		Description: "Update stakeholder request with pan details",
		Req: dtos.StakeholderRequest{
			Kyc: &dtos.Kyc{
				Pan: "ABCPD1234B",
			},
		},
	},
}
