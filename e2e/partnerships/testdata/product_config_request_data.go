package testdata

import "github.com/razorpay/api/e2e/partnerships/dtos"

type ProductConfigTestCase struct {
	Description string
	Req         dtos.ProductConfigRequest
}

type CreateProductConfigTestCase struct {
	Description string
	Req         dtos.ProductConfigCreateRequest
}

var CreateProductConfigTestCases = map[string]CreateProductConfigTestCase{
	"Accept_Product_Tnc": {
		Description: "Create Product config request with accept product tnc",
		Req: dtos.ProductConfigCreateRequest{
			ProductName: "payment_gateway",
			TncAccepted: true,
		},
	},
	"Accept_Product_Tnc_No_Doc": {
		Description: "Create Product config request with accept product tnc",
		Req: dtos.ProductConfigCreateRequest{
			ProductName: "payment_gateway",
			TncAccepted: true,
			Ip:          "223.233.71.29",
		},
	},
}

var UpdateProductConfigTestCases = map[string]ProductConfigTestCase{
	"Update_Sms_Notification": {
		Description: "Update Product Config with sms notification",
		Req: dtos.ProductConfigRequest{
			Notifications: &dtos.Notifications{
				Sms: true,
			},
		},
	},
	"Settlement_Details": {
		Description: "Update Product Config with settlement details for no doc",
		Req: dtos.ProductConfigRequest{
			Settlements: &dtos.Settlements{
				AccountNumber:   1234567890,
				IfscCode:        "UBIN0805165",
				BeneficiaryName: "rahul sharma",
			},
		},
	},
	"No_Doc_Otp_Details": {
		Description: "Update Product Config with Otp details for no doc",
		Req: dtos.ProductConfigRequest{
			Otp: &dtos.Otp{
				ContactMobile:            "7290497229",
				ExternalReferenceNumber:  "123ABCD",
				OtpSubmissionTimestamp:   "363220181001134",
				OtpVerificationTimestamp: "363220181001153",
			},
		},
	},
}
