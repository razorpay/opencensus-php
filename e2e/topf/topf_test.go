package e2e

import (
	"github.com/razorpay/dashboard/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/suite"
	"net/url"
	"testing"
)

var xsrf, session, decodeXsrf string

type TopfAPISuite struct {
	itf.Suite
}

func (s *TopfAPISuite) BeforeTest(suiteName, testName string) {
	// Run statements before every test of the suite.
	xsrf, session = getToken()
	if xsrf != "" {
		decodeXsrf, _ = url.QueryUnescape(xsrf)
	}
}

func (s TopfAPISuite) TestUserRegister() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
	})

	e.DoRequestTests(requestTestForRegister)
}

func (s TopfAPISuite) TestRegisterSendOtp() {

	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
		"X-XSRF-TOKEN":          decodeXsrf,
		"Cookie":                "rzp_usr_session=" + session,
	})

	e.DoRequestTests(requestRegisterOtpSend)
}

func (s TopfAPISuite) TestRegisterVerifyOtp() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
		"X-XSRF-TOKEN":          decodeXsrf,
		"Cookie":                "rzp_usr_session=" + session,
	})

	e.DoRequestTests(requestRegisterOtpVerify)
}

func (s TopfAPISuite) TestUserLogin() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
		"X-XSRF-TOKEN":          decodeXsrf,
		"Cookie":                "rzp_usr_session=" + session,
	})

	e.DoRequestTests(requestTestForLogin)
}

func (s TopfAPISuite) TestLoginSendOtp() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
		"X-XSRF-TOKEN":          decodeXsrf,
		"Cookie":                "rzp_usr_session=" + session,
	})

	e.DoRequestTests(requestLoginOtpSend)
}

func (s TopfAPISuite) TestLoginVerifyOtp() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
		"X-XSRF-TOKEN":          decodeXsrf,
		"Cookie":                "rzp_usr_session=" + session,
	})

	e.DoRequestTests(requestLoginOtpVerify)
}

func TestTopf(t *testing.T) {
	suite.Run(t, &TopfAPISuite{Suite: itf.NewSuite(itf.WithTags([]string{}), itf.WithPriority(itf.PriorityP0))})
}
