package e2e

import (
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/suite"
	"net/http"
	"testing"
)

type ExampleAPITestSuite struct {
	itf.Suite
}

// Each suite can have hooks at suite and test level.
// Ref https://github.com/razorpay/goutils/tree/master/itf#setup--teardown.
// See below examples.

func (s *ExampleAPITestSuite) SetupSuite() {
	s.Suite.SetupSuite()
	// Run statements before the suite finishes.
}

func (s *ExampleAPITestSuite) TearDownSuite() {
	// Run statements after the suite finishes.
}

func (s *ExampleAPITestSuite) BeforeTest(suiteName, testName string) {
	// Run statements before every test of the suite.
}

func (s *ExampleAPITestSuite) AfterTest(suiteName, testName string) {
	// Run statements after every test of the suite.
}

func (s *ExampleAPITestSuite) TestCheck() {
	httpexpect.New(s.T(), Config.App.Hostname).
		GET("/").
		Expect().
		Status(http.StatusOK).
		JSON().Object().ValueEqual("message", "Welcome to Razorpay API.")
}

func TestExampleAPI(t *testing.T) {
	suite.Run(t, &ExampleAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{}), itf.WithPriority(itf.PriorityP0))})
}
