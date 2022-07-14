package linked_account_activation

import (
	"strconv"
	"testing"
	"time"

	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
)

type LinkedAccountPennyTestingApiTestSuite struct {
	itf.Suite
	MerchantId      string
	LinkedAccountId string
}

// Each suite can have hooks at suite and test level.
// Ref https://github.com/razorpay/goutils/tree/master/itf#setup--teardown.
// See below examples.

func (s *LinkedAccountPennyTestingApiTestSuite) SetupSuite() {
	s.Suite.SetupSuite()

	// Run statements before the suite finishes.
	s.MerchantId = "10000000000000"

	//todo:: assign features (marketplace, route_la_penny_testing, la_bank_account_update
	// and direct_transfer) here in suite setup
}

func (s *LinkedAccountPennyTestingApiTestSuite) TearDownSuite() {
	// Run statements after the suite finishes.
}

func (s *LinkedAccountPennyTestingApiTestSuite) BeforeTest(suiteName, testName string) {
	// Run statements before every test of the suite.
	// Enables penny testing feature flag for merchant.
}

func (s *LinkedAccountPennyTestingApiTestSuite) AfterTest(suiteName, testName string) {
	// Run statements after every test of the suite.
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestVerificationPendingStatus() {
	s.T().Skip("Test is intermittently failing because of 503,404 errors")

	reqData := CreateLinkedAccountPositiveTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	reqData.Email = "la-" + uniqueId + "@email.com"
	laRes := CreateLinkedAccount(s.T(), reqData)
	assert.NotNil(s.T(), laRes.ActivationDetails.Status)
	verifyActivationPendingStatus(s.T(), "verification_pending", laRes.ActivationDetails.Status, laRes)
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestFetchMerchantActivationDetails() {
	s.T().Skip("Test is intermittently failing because of 502,404 errors")

	laReq := CreateLinkedAccountPositiveTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Email = "la-" + uniqueId + "@email.com"
	laObj := CreateLinkedAccount(s.T(), laReq)
	laRes := FetchMerchantActivationDetails(s.T(), laObj)
	verifyActivationPendingStatus(s.T(), "verification_pending", laRes.ActivationStatus, laObj)
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestVerificationFailedStatus() {
	s.T().Skip("Test is intermittently failing because of 503,404 errors")

	laReq := CreateLinkedAccountNegativeTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Email = "la-" + uniqueId + "@email.com"
	laObj := CreateLinkedAccount(s.T(), laReq)
	bvsValidationId := GetBvsValidationIdWithOwnerId(laObj.Id)
	SendMockBvsValidationEvent(s.T(), laReq, bvsValidationId)
	laRes := FetchMerchantActivationDetails(s.T(), laObj)
	assert.Equal(s.T(), "verification_failed", laRes.ActivationStatus)
	assert.Equal(s.T(), "invalid data submitted", laRes.BankDetailsVerificationError)
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestVerificationSuccessStatus() {
	s.T().Skip("Test is intermittently failing because of 503,404 errors")

	laReq := CreateLinkedAccountPositiveTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Email = "la-" + uniqueId + "@email.com"
	laObj := CreateLinkedAccount(s.T(), laReq)
	bvsValidationId := GetBvsValidationIdWithOwnerId(laObj.Id)
	SendMockBvsValidationEvent(s.T(), laReq, bvsValidationId)
	laRes := FetchMerchantActivationDetails(s.T(), laObj)
	assert.Equal(s.T(), "activated", laRes.ActivationStatus)
	assert.Equal(s.T(), "", laRes.BankDetailsVerificationError)
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestLinkedAccountBankDetailsUpdate() {
	s.T().Skip("Test is intermittently failing because of 503,404 errors")

	//Updates Bank Details of Linked Account and activation status to verification pending
	laReq := CreateLinkedAccountPositiveTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Email = "la-" + uniqueId + "@email.com"
	laObj := CreateLinkedAccount(s.T(), laReq)
	bankDetailsUpdateReq := BankDetailsUpdateRequestData
	bankDetailsUpdateRes := UpdateLinkedAccountBankDetails(s.T(), laObj.Id, bankDetailsUpdateReq)
	verifyBankDetailsUpdateResponse(s.T(), bankDetailsUpdateReq, bankDetailsUpdateRes)
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestLinkedAccountHoldFundsAfterBankUpdate() {
	s.T().Skip("Test is intermittently failing because of 503,404 errors")

	//Updates Bank Details of Linked Account and activation status to verification pending
	laReq := CreateLinkedAccountPositiveTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Email = "la-" + uniqueId + "@email.com"
	laObj := CreateLinkedAccount(s.T(), laReq)
	bvsValidationId := GetBvsValidationIdWithOwnerId(laObj.Id)
	bankDetailsUpdateReq := BankDetailsUpdateRequestData
	UpdateLinkedAccountBankDetails(s.T(), laObj.Id, bankDetailsUpdateReq)
	holdFundsData := GetHoldFundsData(laObj.Id)
	assert.Equal(s.T(), 1, holdFundsData.HoldFunds)
	assert.Equal(s.T(), "linked_account_penny_testing", holdFundsData.HoldFundsReason)
	SendMockBvsValidationEvent(s.T(), laReq, bvsValidationId)
	holdFundsDataAfterVerification := GetHoldFundsData(laObj.Id)
	assert.Equal(s.T(), 0, holdFundsDataAfterVerification.HoldFunds)
}

func verifyActivationPendingStatus(t *testing.T, expectedStatus string, actualStatus string, linkedAccount LinkedAccountCreateResponse) {
	// asserts verification pending status, if the status is not verification pending
	// Cross verify by fetching status from bvs_validation table
	assert.NotNil(t, actualStatus)
	if actualStatus != expectedStatus {
		validationId := GetBvsValidationIdWithOwnerId(linkedAccount.Id)
		getValidationStatusAndAssert(t, actualStatus, validationId)
	} else {
		assert.Equal(t, expectedStatus, actualStatus)
	}
}

func verifyBankDetailsUpdateResponse(t *testing.T, expected BankDetailsUpdateRequest, actual BankDetailsUpdateResponse) {
	assert.Equal(t, expected.AccountNumber, actual.AccountNumber)
	assert.Equal(t, expected.BeneficiaryName, actual.BeneficiaryName)
	assert.Equal(t, expected.IfscCode, actual.IfscCode)
}

func TestLinkedAccountPennyTestingAPI(t *testing.T) {
	suite.Run(t, &LinkedAccountPennyTestingApiTestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagLinkedAccount}), itf.WithPriority(itf.PriorityP0))})
}
