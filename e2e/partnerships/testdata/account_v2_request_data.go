package testdata

import "github.com/razorpay/api/e2e/partnerships/dtos"

type AccountV2TestCase struct {
	Description string
	Req         dtos.AccountsV2Request
}

var CreateAccountTestCases = map[string]AccountV2TestCase{
	"Only_Mandatory_Fields": {
		Description: "Create account v2 request with only mandatory fields",
		Req: dtos.AccountsV2Request{
			Email:             "rahul@acme.com",
			Phone:             "9820142324",
			LegalBusinessName: "Acme Corp Pvt Ltd",
			BusinessType:      "partnership",
			ReferenceId:       "randomId",
			Profile: &dtos.Profile{
				Category:    "healthcare",
				Subcategory: "clinic",
				Addresses: &dtos.Addresses{
					Registered: &dtos.Address{
						Street1:    "507, Koramangala 1st block",
						Street2:    "MG Road",
						City:       "Bengaluru",
						State:      "Karnataka",
						PostalCode: 560034,
						Country:    "IN",
					}},
			},
		},
	},
	"All_Fields": {
		Description: "Create account v2 request with all fields",
		Req: dtos.AccountsV2Request{
			Email:                      "rahul@acme.com",
			Phone:                      "9820142324",
			LegalBusinessName:          "Acme Corp Pvt Ltd",
			CustomerFacingBusinessName: "Acme",
			BusinessType:               "proprietorship",
			ReferenceId:                "account_COdeRandom",
			Profile: &dtos.Profile{
				Category:    "healthcare",
				Subcategory: "clinic",
				Description: "Healthcare E-commerce platform",
				Addresses: &dtos.Addresses{
					Operation: &dtos.Address{
						Street1:    "507, Koramangala 1st block",
						Street2:    "MG Road",
						City:       "Bengaluru",
						State:      "Karnataka",
						PostalCode: 560034,
						Country:    "IN",
					},
					Registered: &dtos.Address{
						Street1:    "507, Koramangala 1st block",
						Street2:    "MG Road",
						City:       "Bengaluru",
						State:      "Karnataka",
						PostalCode: 560034,
						Country:    "IN",
					},
				},
				BusinessModel: "b2c",
			},
			LegalInfo: &dtos.LegalInfo{
				Pan: "ABCCD1234A",
				Gst: "01AADCB1234M1ZX",
			},
			Brand: &dtos.Brand{
				Color: "FFFFFF",
			},
			Notes: map[string]interface{}{
				"internal_ref_id": "123123",
			},
			TosAcceptance: &dtos.TosAcceptance{
				Date:      nil,
				Ip:        nil,
				UserAgent: "rohit",
			},
			ContactInfo: &dtos.ContactInfo{
				Chargeback: &dtos.Contacts{
					Email:     "cb@acme.org",
					Phone:     "8951496311",
					PolicyUrl: "https://www.google.com",
				},
				Refund: &dtos.Contacts{
					Email:     "cb@acme.org",
					Phone:     "8951496311",
					PolicyUrl: "https://www.google.com",
				},
				Support: &dtos.Contacts{
					Email:     "support@acme.org",
					Phone:     "8951496311",
					PolicyUrl: "https://www.google.com",
				},
			},
			Apps: &dtos.Apps{
				Websites: []string{"https://www.acme.org"},
				Android: []dtos.App{
					{
						URL:  "playstore.acme.org",
						Name: "Acme",
					},
				},
				Ios: []dtos.App{
					{
						URL:  "appstore.acme.org",
						Name: "Acme",
					},
				},
			},
			NoDocOnboarding: false,
		},
	},
	"No_Doc_Fields": {
		Description: "Create account v2 request for no doc",
		Req: dtos.AccountsV2Request{
			Email:                      "testcreateaccount3306@razorpay.com",
			Phone:                      "7290497229",
			LegalBusinessName:          "GKS pvt limited",
			CustomerFacingBusinessName: "GKS",
			BusinessType:               "not_yet_registered",
			ContactName:                "contactname",
			Profile: &dtos.Profile{
				Category:    "healthcare",
				Subcategory: "clinic",
				Description: "Healthcare E-commerce platform",
				Addresses: &dtos.Addresses{
					Registered: &dtos.Address{
						Street1:    "507, Koramangala 1st block",
						Street2:    "MG Road",
						City:       "Bengaluru",
						State:      "Karnataka",
						PostalCode: 560034,
						Country:    "IN",
					},
					Operation: &dtos.Address{
						Street1:    "507, Koramangala 1st block",
						Street2:    "MG Road",
						City:       "Bengaluru",
						State:      "Karnataka",
						PostalCode: 560034,
						Country:    "IN",
					},
				},
				BusinessModel: "b2c",
			},
			NoDocOnboarding: true,
		},
	},
}

var UpdateAccountTestCases = map[string]AccountV2TestCase{
	"Update_Pan": {
		Description: "Update account request with pan details",
		Req: dtos.AccountsV2Request{
			LegalInfo: &dtos.LegalInfo{
				Pan: "ABCCD1234A",
			},
		},
	},
	"Update_Pan2": {
		Description: "Update account request with different pan details",
		Req: dtos.AccountsV2Request{
			LegalInfo: &dtos.LegalInfo{
				Pan: "ABCCD1234B",
			},
		},
	},
}
