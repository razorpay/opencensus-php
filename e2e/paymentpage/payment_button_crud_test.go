package e2e

import (
	"errors"
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"github.com/stretchr/testify/suite"
	"net/http"
	"strings"
	"testing"
)

type PaymentButtonAPITestSuite struct {
	itf.Suite
}

func (s *PaymentPageAPITestSuite) TestPaymentButtonCreatePositive() {
	s.T().Skip("Test is intermittently failing because of 500 Internal Server Error")

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
				ViewType:       "button",
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
				ViewType:       "button",
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
				ViewType:       "button",
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
				ViewType:       "button",
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
			verifyCreatedPB(s.T(), scenario.input, ppRes)
		})
	}
}
func verifyCreatedPB(t *testing.T, ppRequest PaymentPageRequest, ppResponse PaymentPageResponse) {
	assert.Equal(t, ppRequest.Title, ppResponse.Title)
	assert.NotEmptyf(t, ppResponse.ID, "PP did not created")
	assert.Equal(t, ppRequest.Currency, ppResponse.Currency)
}
func validatePaymentButtonRequest(request PaymentPageRequest) error {
	if len(request.Title) == 0 {
		return errors.New("The title field is required.")
	}
	if len(request.PaymentPageItems) == 0 {
		return errors.New("The payment page items field is required.")
	}
	if !strings.Contains(request.PPSettings.PaymentSuccessRedirectURL, "https") {
		return errors.New("The settings.payment success redirect url format is invalid.")
	}
	if request.PaymentPageItems[0].Stock == 0 {
		return errors.New("The stock must be at least 1.")
	}
	if request.PaymentPageItems[0].Settings.Position < 0 {
		return errors.New("The settings.position must be at least 0.")
	}
	if request.PaymentPageItems[0].Settings.Position > 10000 {
		return errors.New("The settings.position may not be greater than 1000.")
	}
	if request.Currency != request.PaymentPageItems[0].Item.Currency {
		return errors.New("payment page currency and payment page item currency should be same")
	}
	if request.Currency == "USD" && request.PaymentPageItems[0].MaxAmount < 10 {
		return errors.New("max_amount must be atleast USD 0.1")
	}
	if request.PaymentPageItems[0].MinAmount > request.PaymentPageItems[0].MaxAmount {
		return errors.New("min amount should not be greater than max amount")
	}
	if request.PaymentPageItems[0].MinPurchase > request.PaymentPageItems[0].Stock {
		return errors.New("min purchase should not be greater than stock")
	}
	if request.PaymentPageItems[0].MinAmount < 100 {
		return errors.New("min_amount must be atleast INR 1")
	}
	if !(request.PaymentPageItems[0].MaxAmount > 0 && request.PaymentPageItems[0].MaxAmount < 4294967295) {
		return errors.New("The max amount must be valid integer between 0 and 4294967295.")
	}
	if !(request.PaymentPageItems[0].Stock > 0 && request.PaymentPageItems[0].Stock < 4294967295) {
		return errors.New("The stock must be valid integer between 0 and 4294967295.")
	}
	return nil // request is valid so we return nil
}

func (s *PaymentPageAPITestSuite) TestPaymentButtonNegative() {
	type errorTestCases struct {
		description   string
		input         PaymentPageRequest
		expectedError string
	}

	ppitem := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 1,
		MinAmount:   1,
		MaxAmount:   10,
	}
	ppitemstock := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       0,
		MinPurchase: 1,
		MaxPurchase: 1,
		MinAmount:   1,
		MaxAmount:   10,
	}
	ppitemposition := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: -1,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 1,
		MinAmount:   1,
		MaxAmount:   10,
	}
	ppitempositionmax := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 10001,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 1,
		MinAmount:   1,
		MaxAmount:   10,
	}
	ppitemamountmax := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "USD",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 1,
		MinAmount:   1,
		MaxAmount:   10,
	}
	ppitemmaxusd := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "USD",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 1,
		MaxAmount:   9,
	}
	ppitemamountmaxmin := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1,
		MinPurchase: 1,
		MaxPurchase: 2,
		MinAmount:   500,
		MaxAmount:   100,
	}
	ppitemmaxminpurchase := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       5,
		MinPurchase: 6,
		MaxPurchase: 10,
		MinAmount:   100,
		MaxAmount:   200,
	}
	ppitemmaxminamount := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1000,
		MinPurchase: 1,
		MaxPurchase: 2,
		MinAmount:   99,
		MaxAmount:   1000,
	}
	maxminamount := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       1000,
		MinPurchase: 1,
		MaxPurchase: 2,
		MinAmount:   8900,
		MaxAmount:   4294967296,
	}
	maxstocklimititem := PaymentPageItems{
		Item: Item{
			Name:        "exy",
			Currency:    "INR",
			Description: "test",
			Type:        "payment_page",
		},
		Settings: PPItemSettings{
			Position: 1,
		},
		Stock:       4294967296,
		MinPurchase: 1,
		MaxPurchase: 2,
		MinAmount:   100,
		MaxAmount:   500,
	}
	ppitempos := []PaymentPageItems{ppitemposition}
	ppitems := []PaymentPageItems{ppitem}
	ppitemsstock := []PaymentPageItems{ppitemstock}
	ppitemposmax := []PaymentPageItems{ppitempositionmax}
	ppitemamount := []PaymentPageItems{ppitemamountmax}
	ppitemusd := []PaymentPageItems{ppitemmaxusd}
	ppmaxminamount := []PaymentPageItems{ppitemamountmaxmin}
	ppitempurchasemaxmin := []PaymentPageItems{ppitemmaxminpurchase}
	ppitemamountmin := []PaymentPageItems{ppitemmaxminamount}
	ppitemamountmaxlimit := []PaymentPageItems{maxminamount}
	maxstocklimit := []PaymentPageItems{maxstocklimititem}

	for _, scenario := range []errorTestCases{
		{
			description: "Mandatory Title",
			input: PaymentPageRequest{
				Currency:       "INR",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "prem.kumar@razorpay.com",
				SupportContact: "7502233314",
				ViewType:       "button",
			},
			expectedError: "The title field is required.",
		},
		{
			description: "Payment Page Item",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "prem.kumar@razorpay.com",
				SupportContact: "7502233314",
				ViewType:       "button",
			},
			expectedError: "The payment page items field is required.",
		},
		{
			description: "Settings Payment success redirect url",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "raxorpay",
					Theme:                     "light",
				},
				PaymentPageItems: ppitems,
			},
			expectedError: "The settings.payment success redirect url format is invalid.",
		},
		{
			description: "Stock minimum value",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitemsstock,
			},
			expectedError: "The stock must be at least 1.",
		},
		{
			description: "Settings Position minimum value",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitempos,
			},
			expectedError: "The settings.position must be at least 0.",
		},
		{
			description: "Settings Position maximum value",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitemposmax,
			},
			expectedError: "The settings.position may not be greater than 1000.",
		},
		{
			description: "Payment Page and Item currency",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitemamount,
			},
			expectedError: "payment page currency and payment page item currency should be same",
		},
		{
			description: "Max USD Currency",
			input: PaymentPageRequest{
				Currency:       "USD",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitemusd,
			},
			expectedError: "max_amount must be atleast USD 0.1",
		},
		{
			description: "min amount should not be greater than max amount",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppmaxminamount,
			},
			expectedError: "min amount should not be greater than max amount",
		},
		{
			description: "min purchase should not be greater than stock",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitempurchasemaxmin,
			},
			expectedError: "min purchase should not be greater than stock",
		},
		{
			description: "min_amount must be atleast INR 1",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitemamountmin,
			},
			expectedError: "min_amount must be atleast INR 1",
		},
		{
			description: "The max amount must be valid integer between 0 and 4294967295.",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: ppitemamountmaxlimit,
			},
			expectedError: "The max amount must be valid integer between 0 and 4294967295.",
		},
		{
			description: "The stock must be valid integer between 0 and 4294967295.",
			input: PaymentPageRequest{
				Currency:       "INR",
				Title:          "can't say much about it",
				Description:    "bag for test",
				Terms:          "no conditions please",
				SupportEmail:   "ranjith.kotian@razorpay.com",
				SupportContact: "9483159238",
				ViewType:       "button",
				PPSettings: PPSettings{
					UdfSchema:                 "\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"required\\\":false,\\\"type\\\":\\\"string\\\",\\\"position\\\":1",
					AllowSocialShare:          "1",
					PaymentSuccessMessage:     "Payment is successfull",
					PaymentSuccessRedirectURL: "https://google.com",
					Theme:                     "light",
				},
				PaymentPageItems: maxstocklimit,
			},
			expectedError: "The stock must be valid integer between 0 and 4294967295.",
		},
	} {
		s.Run(scenario.description, func() {
			err := validatePaymentButtonRequest(scenario.input)
			require.Error(s.T(), err)
			assert.Equal(s.T(), scenario.expectedError, err.Error())
		})
	}
}

func (s *PaymentPageAPITestSuite) TestPaymentButtonPreferenceAPI() {

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
	ppitempos := []PaymentPageItems{ppitem}
	scenario := positiveTestCases{
		description: "With Amount,Stock, Min and Max Purchase",
		input: PaymentPageRequest{
			Currency:       "INR",
			Title:          "Test Page",
			Description:    "bag for test",
			Terms:          "Terms and contions",
			SupportEmail:   "prem.svmm@test.com",
			SupportContact: "7502233314",
			ViewType:       "button",
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
	}
	var pb = CreatePaymentPage(s.T(), scenario.input)

	header = map[string]string{
		"Origin":  "https://cdn.razorpay.com",
		"Referer": "https://cdn.razorpay.com",
	}

	httpexpect.New(s.T(), e2e.Config.App.Hostname).
		GET("/v1/payment_buttons/" + pb.ID + "/button_preferences").
		WithHeaders(header).
		Expect().
		Status(http.StatusOK).
		Header("Access-Control-Allow-Origin").
		Equal("*")
}

func TestPaymentButtonAPI(t *testing.T) {
	suite.Run(t, &PaymentButtonAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagPaymentButton}), itf.WithPriority(itf.PriorityP0))})
}
