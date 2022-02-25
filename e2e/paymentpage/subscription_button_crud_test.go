package e2e

import (
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"testing"
)

type SubscriptionButtonAPITestSuite struct {
	itf.Suite
}

func (s *PaymentPageAPITestSuite) TestSubscriptionButtonCreatePositive() {
	type positiveTestCases struct {
		description string
		input       PaymentPageRequest
	}
	ppitem := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
			Amount:      100,
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 2,
	}
	ppitemus := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "USD",
			Description: "test",
			Type:        "payment_page",
			Amount:      100,
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 1,
	}
	ppitempos := []PaymentPageItems{ppitem}
	ppitemusd := []PaymentPageItems{ppitemus}
	for _, scenario := range []positiveTestCases{
		{
			description: "With Amount,Stock, Min and Max Purchase",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "Test Page",
				Description:    "bag for test",
				Terms:          "Terms and contions",
				SupportEmail:   "prem.svmm@test.com",
				SupportContact: "7502233314",
				ViewType:       "subscription_button",
				PPSettings: PPSettings{
					UdfSchema:                 "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":2}}]",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
					PaymentButtonText:         "Please Pay",
					PaymentButtonTheme:        "light",
					PpButtonDisableBranding:   "1",
				},
				PaymentPageItems: ppitempos,
			},
		},
		{
			description: "Merchant Risk Service Title",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "Passport Passport Passport",
				Description:    "bag for test",
				Terms:          "Terms and contions",
				SupportEmail:   "prem.svmm@test.com",
				SupportContact: "7502233314",
				ViewType:       "subscription_button",
				PPSettings: PPSettings{
					UdfSchema:                 "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":2}}]",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
					PaymentButtonText:         "Please Pay",
					PaymentButtonTheme:        "light",
					PpButtonDisableBranding:   "1",
				},
				PaymentPageItems: ppitempos,
			},
		},
		{
			description: "Merchant Risk Service Description",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "test",
				Description:    "Passport Passport Passport",
				Terms:          "Terms and contions",
				SupportEmail:   "prem.svmm@test.com",
				SupportContact: "7502233314",
				ViewType:       "subscription_button",
				PPSettings: PPSettings{
					UdfSchema:                 "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":2}}]",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
					PaymentButtonText:         "Please Pay",
					PaymentButtonTheme:        "light",
					PpButtonDisableBranding:   "1",
				},
				PaymentPageItems: ppitempos,
			},
		},
		{
			description: "USD Currency",
			input: PaymentPageRequest{
				Currency:       "USD",
				Title:          "test",
				Description:    "Passport Passport Passport",
				Terms:          "Terms and contions",
				SupportEmail:   "prem.svmm@test.com",
				SupportContact: "7502233314",
				ViewType:       "subscription_button",
				PPSettings: PPSettings{
					UdfSchema:                 "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":2}}]",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
					PaymentButtonText:         "Please Pay",
					PaymentButtonTheme:        "light",
					PpButtonDisableBranding:   "1",
				},
				PaymentPageItems: ppitemusd,
			},
		},
	} {
		s.Run(scenario.description, func() {
			ppRes := CreatePaymentPage(s.T(), scenario.input)
			verifyCreatedSB(s.T(), scenario.input, ppRes)
		})
	}
}
func verifyCreatedSB(t *testing.T, ppRequest PaymentPageRequest, ppResponse PaymentPageResponse) {
	assert.Equal(t, ppRequest.Title, ppResponse.Title)
	assert.NotEmptyf(t, ppResponse.ID, "PP did not created")
	assert.Equal(t, ppRequest.Currency, ppResponse.Currency)
}
func TestSubscriptionButtonAPITestSuite(t *testing.T) {
	suite.Run(t, &SubscriptionButtonAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagSubscriptionButton}), itf.WithPriority(itf.PriorityP0))})
}
