package e2e

import (
	"github.com/razorpay/dashboard/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/suite"
	"golang.org/x/net/html"
	"net/url"
	"regexp"
	"strings"
	"testing"
)

var xsrf, session, decodeXsrf string

type DashboardAPISuite struct {
	itf.Suite
}

func (s *DashboardAPISuite) BeforeTest(suiteName, testName string) {
	// Run statements before every test of the suite.

	if strings.Contains(testName, "LAPostLogin") {
		xsrf, session = getTokenAfterLogin(dashboardLATestCreds)
	} else if strings.Contains(testName, "PostLogin") {
		xsrf, session = getTokenAfterLogin(dashboardTestCreds)
	} else {
		xsrf, session = getTokenBeforeLogin()
	}

	if xsrf != "" {
		decodeXsrf, _ = url.QueryUnescape(xsrf)
	}
}

func (s DashboardAPISuite) TestGetIndexPostLogin() {
	var response string
	response = GetIndexRouteCall(s)

	matched, err := regexp.MatchString(`window.rzp_user = {"current":"J9fikDGL4m3rCy".*`, response)
	if err != nil {
		s.T().Fatal(err.Error())
	}

	if matched == false {
		s.T().Fatalf("Not Logged In")
	}

	for _, component := range componentsToAssert {
		if !strings.Contains(response, component) {
			s.T().Fatalf(`Expected component "%s" not found`, component)
		}
	}
}

func (s DashboardAPISuite) TestGetIndexBeforeLogin() {
	var response string
	response = GetIndexRouteCall(s)

	responseComponents := append(componentsToAssert, "window.isAuthPage = true")

	for _, component := range responseComponents {
		if !strings.Contains(response, component) {
			s.T().Fatalf(`Expected component "%s" not found`, component)
		}
	}
}

func (s DashboardAPISuite) TestGetIndexLAPostLogin() {
	var response string
	response = GetIndexRouteCall(s)

	matched, err := regexp.MatchString(`window.rzp_user = {"current":"AixKlbbNt84nkl".*};`, response)
	if err != nil {
		s.T().Fatal(err.Error())
	}

	if matched == false {
		s.T().Fatalf("Not Logged In")
	}

	responseComponents := append(componentsToAssert, `"linked_account": true`)

	for _, component := range responseComponents {
		if strings.Contains(response, component) {
			s.T().Fatalf(`"%s" should not be present`, component)
		}
	}
}

func GetIndexRouteCall(s DashboardAPISuite) string {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		DEVSTACK_LABEL_HEADER: e2e.DevServeHeader,
		HEADER_X_XSRF_TOKEN:   decodeXsrf,
		COOKIE:                RZP_USER_SESSION + "=" + session,
	})

	res := e.DoRequestTests(requestTestGetIndex)

	var response string
	response = res[VALID_REQUEST].Body().Raw()

	_, err := html.Parse(strings.NewReader(response))

	if err != nil {
		s.T().Fatal(err.Error())
	}

	return response
}

func TestDashboard(t *testing.T) {
	suite.Run(t, &DashboardAPISuite{Suite: itf.NewSuite(itf.WithTags([]string{}), itf.WithPriority(itf.PriorityP0))})
}
