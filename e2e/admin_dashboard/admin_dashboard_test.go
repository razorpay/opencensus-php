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

type AdminDashboardAPISuite struct {
	itf.Suite
}

func (s *AdminDashboardAPISuite) BeforeTest(suiteName, testName string) {
	// Run statements before every test of the suite.

	if strings.Contains(testName, "PostLogin") {
		xsrf, session = getTokenAfterLogin()
	} else {
		xsrf, session = getTokenBeforeLogin()
	}

	if xsrf != "" {
		decodeXsrf, _ = url.QueryUnescape(xsrf)
	}
}

func (s AdminDashboardAPISuite) TestGetIndexPostLogin() {
	var response string
	response = GetIndexRouteCall(s)

	matched, err := regexp.MatchString(`user = {"id":"admin_KdSGHSeDZTGFDD","email":"admine2etest@razorpay.com".*`, response)
	if err != nil {
		s.T().Fatal(err.Error())
	}

	if matched == false {
		s.T().Fatalf("Admin Not Logged In")
	}

	matched, err = regexp.MatchString(`var org = {"id":"org_100000razorpay".*`, response)
	if err != nil {
		s.T().Fatal(err.Error())
	}

	if matched == false {
		s.T().Fatalf("Admin Not Logged In To RazorPay Org")
	}

	for _, component := range componentsToAssertPostLogin {
		if !strings.Contains(response, component) {
			s.T().Fatalf(`Expected component "%s" not found`, component)
		}
	}
}

func (s AdminDashboardAPISuite) TestGetIndexBeforeLogin() {
	var response string
	response = GetIndexRouteCall(s)

	for _, component := range componentsToAssertBeforeLogin {
		if !strings.Contains(response, component) {
			s.T().Fatalf(`Expected component "%s" not found`, component)
		}
	}
}

func GetIndexRouteCall(s AdminDashboardAPISuite) string {
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

func TestAdminDashboard(t *testing.T) {
	suite.Run(t, &AdminDashboardAPISuite{Suite: itf.NewSuite(itf.WithTags([]string{}), itf.WithPriority(itf.PriorityP0))})
}
