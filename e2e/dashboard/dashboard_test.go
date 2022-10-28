package e2e

import (
	"github.com/razorpay/dashboard/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/suite"
	"golang.org/x/net/html"
	"net/url"
	"strings"
	"testing"
)

var xsrf, session, decodeXsrf string

type DashboardAPISuite struct {
	itf.Suite
}

func (s *DashboardAPISuite) BeforeTest(suiteName, testName string) {
	// Run statements before every test of the suite.
	xsrf, session = getToken()
	if xsrf != "" {
		decodeXsrf, _ = url.QueryUnescape(xsrf)
	}
}

func (s DashboardAPISuite) TestGetIndex() {
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

	responseComponents := []string{
		`<title>Razorpay Dashboard</title>`,
		`<meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />`,
	}

	for _, component := range responseComponents {
		if !strings.Contains(response, component) {
			s.T().Fatalf(`Expected component "%s" not found`, component)
		}
	}
}

func TestDashboard(t *testing.T) {
	suite.Run(t, &DashboardAPISuite{Suite: itf.NewSuite(itf.WithTags([]string{}), itf.WithPriority(itf.PriorityP0))})
}
