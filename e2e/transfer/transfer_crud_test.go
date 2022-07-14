package transfer

import (
	// "fmt"
	// "net/http"
	"testing"

	linkedAccount "github.com/razorpay/api/e2e/linkedaccountactivation"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
)

type TransferApiTestSuite struct {
	itf.Suite
	parentMerchantId string
	linkedAccountId  string
}

// Each suite can have hooks at suite and test level.
// Ref https://github.com/razorpay/goutils/tree/master/itf#setup--teardown.
// See below examples.

func (s *TransferApiTestSuite) SetupSuite() {
	s.Suite.SetupSuite()
	// Run statements before the suite finishes.

	s.parentMerchantId = "10000000000000"
}

func (s *TransferApiTestSuite) TearDownSuite() {
	// Run statements after the suite finishes.
}

func (s *TransferApiTestSuite) BeforeTest(suiteName, testName string) {
	// Run statements before every test of the suite.
}

func (s *TransferApiTestSuite) AfterTest(suiteName, testName string) {
	// Run statements after every test of the suite.
}

func (s *TransferApiTestSuite) TestTransferToVerificationPendingAccountInTestMode() {
	s.T().Skip("Test is intermittently failing because of 400 error")

	// Should be able to create transfers in test mode
	linkedAccountReq := linkedAccount.CreateLinkedAccountNegativeTestCases[0].Req
	laRes := linkedAccount.CreateLinkedAccount(s.T(), linkedAccountReq)
	transferRequestData := CreateDirectTransferRequest{
		Account:  laRes.Id,
		Amount:   100,
		Currency: "INR",
	}

	transferResponse := CreateDirectTransferPositive(s.T(), transferRequestData)
	assert.Equal(s.T(), transferRequestData.Account, transferResponse.Recipient)
	assert.NotNil(s.T(), transferResponse.ID)

}
//TODO: Remove funds on hold for merchant 10000000000000 and then enable this test case

// func (s *TransferApiTestSuite) TestTransferToVerificationPendingAccountInLiveMode() {
// 	// Should be able to create transfers in test mode
//
// 	linkedAccountReq := linkedAccount.CreateLinkedAccountNegativeTestCases[0].Req
// 	laRes := linkedAccount.CreateLinkedAccount(s.T(), linkedAccountReq)
// 	transferRequestData := CreateDirectTransferRequest{
// 		Account:  laRes.Id,
// 		Amount:   100,
// 		Currency: "INR",
// 	}
// 	transferErrorData := ErrorResponse{
// 		Error: &Error{
// 			Code:        "BAD_REQUEST_ERROR",
// 			Description: "Bank account verification is pending for this linked account",
// 		},
// 	}
// 	errorResponse := CreateDirectTransferNegative(s.T(), transferRequestData)
// 	fmt.Println("Error resp", errorResponse.Error)
// 	assert.Equal(s.T(), transferErrorData.Error.Code, errorResponse.Error.Code)
// 	assert.Equal(s.T(), transferErrorData.Error.Description, errorResponse.Error.Description)
// }

func TestTransferAPI(t *testing.T) {
	suite.Run(t, &TransferApiTestSuite{Suite: itf.NewSuite(itf.WithTags([]string{"transfer"}), itf.WithPriority(itf.PriorityP0))})
}
