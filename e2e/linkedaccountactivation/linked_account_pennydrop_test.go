package e2e

import (
	"encoding/json"
	// "fmt"
	"net/http"
	"os"
	"strconv"
	"testing"
	"time"

	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	// "github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
)

type LinkedAccountPennyTestingApiTestSuite struct {
	itf.Suite
	MerchantId string
	LinkedAccountId string
}

// Each suite can have hooks at suite and test level.
// Ref https://github.com/razorpay/goutils/tree/master/itf#setup--teardown.
// See below examples.

func (s *LinkedAccountPennyTestingApiTestSuite) SetupSuite() {
	s.Suite.SetupSuite()
	// Run statements before the suite finishes.
	s.MerchantId = "10000000000000"

	//todo:: assign features marketplace and route_la_penny_testing here in suite setup
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

// func (s *LinkedAccountPennyTestingApiTestSuite) TestVerificationPendingStatus() {
//     // todo remove this check
//     // intermittently failing with expected: "verification_pending" actual : "verification_failed"
// 	s.T().SkipNow()
// 	dir, _ := os.Getwd()
// 	var laRes LinkedAccountCreateResponse
// 	laReq, _ := GetLinkedAccountCreateRequest(dir + "/../linkedaccountactivation/linkedaccountcreate.json")
// 	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
// 	laReq.Name = "LA " + uniqueId
// 	laReq.Email = "la-" + uniqueId + "@email.com"
//
// 	obj := httpexpect.New(s.T(), e2e.Config.App.Hostname).
// 		POST("/v1/beta/accounts").
// 		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
// 		WithHeaders(map[string]string{
// 			"X-Dashboard-User-id":   e2e.Config.SubMerchant.User,
// 			"X-Dashboard-User-Role": e2e.Config.SubMerchant.Role,
// 			"Content-Type":          "application/json",
// 		}).
// 		WithJSON(laReq).
// 		Expect().
// 		Status(http.StatusOK).Body()
//
// 	json.Unmarshal([]byte(obj.Raw()), &laRes)
// 	assert.NotNil(s.T(), laRes.ActivationDetails.Status)
// 	assert.Equal(s.T(), "verification_pending", laRes.ActivationDetails.Status)
// }

func createLinkedAccount(t *testing.T) LinkedAccountCreateResponse {

	dir, _ := os.Getwd()
	laReq, _ := GetLinkedAccountCreateRequest(dir + "/../linkedaccountactivation/linkedaccountcreate.json")

	var laRes LinkedAccountCreateResponse

	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Name = "LA " + uniqueId
	laReq.Email = "la-" + uniqueId + "@email.com"

	obj := httpexpect.New(t, e2e.Config.App.Hostname).
		POST("/v1/beta/accounts").
		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
		WithHeaders(map[string]string{
			"X-Dashboard-User-id":   e2e.Config.SubMerchant.User,
			"X-Dashboard-User-Role": e2e.Config.SubMerchant.Role,
			"Content-Type":          "application/json",
		}).
		WithJSON(laReq).
		Expect().
		Status(http.StatusOK).Body()

	json.Unmarshal([]byte(obj.Raw()), &laRes)

	return laRes
}

// func (s *LinkedAccountPennyTestingApiTestSuite) TestFetchMerchantActivationDetails() {
//     //todo:: remove this check
// 	s.T().SkipNow()
// 	laObj := createLinkedAccount(s.T())
// 	fmt.Println(laObj)
// 	var laRes MerchantActivationDetails
// 	res := httpexpect.New(s.T(), e2e.Config.App.Hostname).
// 		GET("/v1/merchant/activation").
// 		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
// 		WithHeaders(map[string]string{
// 			"X-Razorpay-Account":    laObj.Id,
// 			"X-Dashboard-User-id":   e2e.Config.SubMerchant.User,
// 			"X-Dashboard-User-Role": e2e.Config.SubMerchant.Role,
// 		}).
// 		Expect().
// 		Status(http.StatusOK).Body()
//
// 	json.Unmarshal([]byte(res.Raw()), &laRes)
// 	assert.Equal(s.T(), "verification_pending", laRes.ActivationStatus)
//
// }

func (s *LinkedAccountPennyTestingApiTestSuite) TestCheck() {
	httpexpect.New(s.T(), e2e.Config.App.Hostname).
		GET("/").
		Expect().
		Status(http.StatusOK).
		JSON().Object().ValueEqual("message", "Welcome to Razorpay API.")
}

func TestLinkedAccountPennyTestingAPI(t *testing.T) {
	suite.Run(t, &LinkedAccountPennyTestingApiTestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagLinkedAccount}), itf.WithPriority(itf.PriorityP0))})
}
