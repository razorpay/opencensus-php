package qrcodes

import (
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"testing"
)

type QrCodesAPITestSuite struct {
	itf.Suite
}

func (s *QrCodesAPITestSuite) TestQrCodeCreate() {
	s.T().Skip("Test is intermittently failing for a few test cases")

	for _, positiveScenario := range CreateQrCodesPositiveTests {
		s.Run(positiveScenario.description, func() {
			qrRes := CreateQrCode(s.T(), positiveScenario.req)
			verifyCreatedQrCode(s.T(), positiveScenario.req, qrRes)
		})
	}

	for _, negativeScenario := range CreateQrCodesNegativeTests {
		s.Run(negativeScenario.description, func() {
			errorResponse := CreateQrCodeNegative(s.T(), negativeScenario.req)
			verifyErrorResponse(s.T(), negativeScenario.errorResponse, errorResponse)
		})
	}
}

func (s *QrCodesAPITestSuite) TestQrCodeClose() {
	s.Run("QR Codes close positive", func() {
		qrRes := CreateQrCode(s.T(), CreateQrCodesPositiveTests[0].req)
		qrRes = CloseQrCodePositive(s.T(), qrRes.Id)
		assert.Equal(s.T(), "closed", qrRes.Status)
	})

	s.Run("QR Codes close - Qr code id doesn't exist", func() {
		errorResponse := CloseQrCodeNegative(s.T(), "qr_qrv21234567890")
		assert.Equal(s.T(), "The id provided does not exist", errorResponse.Error.Description)
	})
}

func (s *QrCodesAPITestSuite) TestQrCodeFetch() {
	s.T().Skip("Test is intermittently failing because of 503 error")

	for _, scenario := range FetchQrCodesTests {
		s.Run(scenario.description, func() {
			for _, qrCreateRequest := range scenario.createRequests {
				CreateQrCode(s.T(), qrCreateRequest)
			}
			qrFetchResponse := FetchQrCode(s.T(), scenario.fetchRequest)
			verifyFetchQrCodeResponse(s.T(), qrFetchResponse, scenario.fetchResponse)
		})
	}
}

func (s *QrCodesAPITestSuite) TestQrPaymentSharpGateway() {
	s.T().Skip("Test is intermittently failing because of 504 error in test case 'TestQrPaymentSharpGateway/Upi_Payment' ")

	for _, scenario := range QrPaymentSharpPos {
		s.Run(scenario.description, func() {
			createResponse := CreateQrCode(s.T(), scenario.createReq)
			paymentData := scenario.payRequest
			paymentData.Reference = createResponse.Id[3:] + "qrv2"
			ProcessPaymentCallbackSharpGateway(s.T(), paymentData)
			qrPaymentFetchResponse := FetchQrPaymentsForQrCode(s.T(), createResponse.Id)
			verifyQrPaymentResponse(s.T(), qrPaymentFetchResponse)
		})
	}
}

func verifyQrPaymentResponse(t *testing.T, actual QrPaymentFetchResponse) {
	assert.Equal(t, 1, actual.Count)
}

func verifyErrorResponse(t *testing.T, expected ErrorResponse, actual ErrorResponse) {
	assert.Equal(t, expected.Error.Code, actual.Error.Code)
	assert.Equal(t, expected.Error.Description, actual.Error.Description)
	assert.Equal(t, expected.Error.Source, actual.Error.Source)
	assert.Equal(t, expected.Error.Step, actual.Error.Step)
	assert.Equal(t, expected.Error.Reason, actual.Error.Reason)
}

func verifyCreatedQrCode(t *testing.T, req QrCodeCreateRequest, res QrCodeCreateResponse) {
	assert.Equal(t, req.Name, res.Name)
	assert.Equal(t, req.CloseBy, res.CloseBy)
	assert.Equal(t, req.Usage, res.Usage)
	assert.Equal(t, req.Type, res.Type)
	assert.Equal(t, req.Description, res.Description)
	assert.Equal(t, "active", res.Status)
	assert.Equal(t, req.CustomerId, res.CustomerId)
}

func verifyFetchQrCodeResponse(t *testing.T, actual QrCodeFetchResponse, expected QrCodeFetchResponse) {
	assert.Equal(t, expected.Count, actual.Count)
}

func TestQrCodeAPI(t *testing.T) {
	suite.Run(t, &QrCodesAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{"qr_codes"}), itf.WithPriority(itf.PriorityP0))})
}
