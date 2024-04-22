package e2e

import (
	"bytes"
	"encoding/json"
	"github.com/razorpay/dashboard/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"golang.org/x/net/html"
	"net/http"
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

func (s DashboardAPISuite) TestAccessTokenCookieOnLogin() {

	var xsrfCookie, sessionID string

	req, err := http.NewRequest(GET_METHOD, e2e.Config.App.Hostname+PATH_ORG, nil)

	if err != nil {
		s.T().Fatal(err)
	}

	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)

	resp, err := http.DefaultClient.Do(req)

	if err != nil {
		s.T().Fatal(err)
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			xsrfCookie, _ = url.QueryUnescape(cookie.Value)
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			sessionID = cookie.Value
		}
	}

	jsonData, err := json.Marshal(AccessTokenCreds)

	if err != nil {
		s.T().Fatal(err)
	}

	req, err = http.NewRequest(POST_METHOD, e2e.Config.App.Hostname+PATH_USER_SIGNIN, bytes.NewBuffer(jsonData))

	if err != nil {
		s.T().Fatal(err)
	}

	req.Header.Set(HEADER_CONTENT_TYPE, APPLICATION_JSON)
	req.Header.Set(HEADER_X_XSRF_TOKEN, xsrfCookie)
	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)
	req.Header.Set(ORIGIN, DASHBOARD_ORIGIN)

	cookie := http.Cookie{Name: COOKIE_RZP_USR_SESSION, Value: sessionID}
	req.AddCookie(&cookie)

	resp, err = http.DefaultClient.Do(req)

	if err != nil {
		s.T().Fatal(err)
	}

	var accessTokenCookie string
	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_RZP_ACCESS_TOKEN {
			accessTokenCookie, _ = url.QueryUnescape(cookie.Value)
		}

	}

	assert.NotEmpty(s.T(), accessTokenCookie)

}

func (s DashboardAPISuite) TestClearAccessTokenCookieOnLogout() {

	var xsrfToken, sessionID string

	req, err := http.NewRequest(GET_METHOD, e2e.Config.App.Hostname+PATH_ORG, nil)

	if err != nil {
		s.T().Fatal(err)
	}

	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)

	resp, err := http.DefaultClient.Do(req)

	if err != nil {
		s.T().Fatal(err)
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			xsrfToken, _ = url.QueryUnescape(cookie.Value)
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			sessionID = cookie.Value
		}
	}

	jsonData, err := json.Marshal(AccessTokenCreds)

	if err != nil {
		s.T().Fatal(err)
	}

	req, err = http.NewRequest(POST_METHOD, e2e.Config.App.Hostname+PATH_USER_SIGNIN, bytes.NewBuffer(jsonData))

	if err != nil {
		s.T().Fatal(err)
	}

	req.Header.Set(HEADER_CONTENT_TYPE, APPLICATION_JSON)
	req.Header.Set(HEADER_X_XSRF_TOKEN, xsrfToken)
	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)
	req.Header.Set(ORIGIN, DASHBOARD_ORIGIN)

	sessionCookie := http.Cookie{Name: COOKIE_RZP_USR_SESSION, Value: sessionID}
	req.AddCookie(&sessionCookie)

	resp, err = http.DefaultClient.Do(req)

	if err != nil {
		s.T().Fatal(err)
	}

	var accessTokenCookie string
	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_RZP_ACCESS_TOKEN {
			accessTokenCookie, _ = url.QueryUnescape(cookie.Value)
		}

	}

	assert.NotEmpty(s.T(), accessTokenCookie)

	req, err = http.NewRequest(POST_METHOD, e2e.Config.App.Hostname+PATH_USER_LOGOUT, nil)

	if err != nil {
		s.T().Fatal(err)
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			xsrfToken, _ = url.QueryUnescape(cookie.Value)
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			sessionID = cookie.Value
		}
	}

	req.Header.Set(HEADER_CONTENT_TYPE, APPLICATION_JSON)
	req.Header.Set(HEADER_X_XSRF_TOKEN, xsrfToken)
	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)
	req.Header.Set(ORIGIN, DASHBOARD_ORIGIN)

	sessionCookie = http.Cookie{Name: COOKIE_RZP_USR_SESSION, Value: sessionID}
	req.AddCookie(&sessionCookie)

	resp, err = http.DefaultClient.Do(req)

	if err != nil {
		s.T().Fatal(err)
	}

	var accessTokenCookieMaxAge int
	var expireCookieSet bool

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_RZP_ACCESS_TOKEN {
			expireCookieSet = true
			accessTokenCookieMaxAge = cookie.MaxAge
		}

	}

	assert.True(s.T(), expireCookieSet)
	// MaxAge<0 means delete cookie now, equivalently 'Max-Age: 0'
	assert.Equal(s.T(), accessTokenCookieMaxAge, -1)

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
