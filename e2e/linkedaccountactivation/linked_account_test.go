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
	reqData := CreateLinkedAccountPositiveTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	reqData.Email = "la-" + uniqueId + "@email.com"
	laRes := CreateLinkedAccount(s.T(), reqData)
	assert.NotNil(s.T(), laRes.ActivationDetails.Status)
	verifyActivationPendingStatus(s.T(), "verification_pending", laRes.ActivationDetails.Status, laRes)
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestFetchMerchantActivationDetails() {
	laReq := CreateLinkedAccountPositiveTestCases[0].Req
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Email = "la-" + uniqueId + "@email.com"
	laObj := CreateLinkedAccount(s.T(), laReq)
	laRes := FetchMerchantActivationDetails(s.T(), laObj)
	verifyActivationPendingStatus(s.T(), "verification_pending", laRes.ActivationStatus, laObj)
}

func (s *LinkedAccountPennyTestingApiTestSuite) TestVerificationFailedStatus() {
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

func TestLinkedAccountPennyTestingAPI(t *testing.T) {
	suite.Run(t, &LinkedAccountPennyTestingApiTestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagLinkedAccount}), itf.WithPriority(itf.PriorityP0))})
}
