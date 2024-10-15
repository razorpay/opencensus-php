package e2e

import (
	"net/url"
	"testing"

	"github.com/razorpay/dashboard/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/suite"
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

	// Skipping this test as it is failing due to devstack issue.
	s.T().Skip()

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

	// Skipping this test as it is always failing
	s.T().Skip()

	e.DoRequestTests(requestRegisterOtpSend)
}

func (s TopfAPISuite) TestRegisterVerifyOtp() {

	// Skipping this test as it is failing due to devstack issue.
	s.T().Skip()

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

	// Skipping this test as it is unstable. Throws BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED error occasionally
	s.T().Skip()

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

func (s TopfAPISuite) TestUserOnlyLoginToUserWithhSingleMerchantOnDashboard() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
		"X-XSRF-TOKEN":          decodeXsrf,
		"Cookie":                "rzp_usr_session=" + session,
	})

	userOnly := true

	response := e.POST("/user/signin").
		WithJSON(&LoginSignUpUserRequest{
			Email:    "userWithSingleMerchant@razorpay.com",
			Password: "1p1p1p1p1p@",
			Captcha:  "Faked",
			UserOnly: &userOnly,
		}).
		Expect()

	// Since there is only one merchant for the user, login is successful and currentMerchantId is present in the response
	response.JSON().Object().Path("$.data").Object().ContainsKey("currentMerchantId")
}

func (s TopfAPISuite) TestUserOnlyLoginToUserWithhSingleMerchantOnUsl() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader:     e2e.DevServeHeader,
		"X-XSRF-TOKEN":              decodeXsrf,
		"Cookie":                    "rzp_usr_session=" + session,
		"apollographql-client-name": "frontend-auth",
	})

	userOnly := true

	response := e.POST("/user/signin").
		WithJSON(&LoginSignUpUserRequest{
			Email:    "userWithSingleMerchant@razorpay.com",
			Password: "1p1p1p1p1p@",
			Captcha:  "Faked",
			UserOnly: &userOnly,
		}).
		Expect()

	// Since there is only one merchant for the user, login is successful and currentMerchantId is present in the response
	response.JSON().Object().Path("$.data").Object().ContainsKey("currentMerchantId")
}

func (s TopfAPISuite) TestUserOnlyLoginToUserWithMultipleMerchantsOnDashboard() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader: e2e.DevServeHeader,
		"X-XSRF-TOKEN":          decodeXsrf,
		"Cookie":                "rzp_usr_session=" + session,
	})

	userOnly := true

	response := e.POST("/user/signin").
		WithJSON(&LoginSignUpUserRequest{
			Email:    "userWithMultipleMerchants@razorpay.com",
			Password: "1p1p1p1p1p@",
			Captcha:  "Faked",
			UserOnly: &userOnly,
		}).
		Expect()

	// Even though there are multiple merchants for the user, login is successful and currentMerchantId is present in the response
	// This is because the mutli account flow is only available from USL
	response.JSON().Object().Path("$.data").Object().ContainsKey("currentMerchantId")
}

func (s TopfAPISuite) TestUserOnlyLoginToUserWithMultipleMerchantsOnUsl() {
	e := httpexpect.NewWithHeaders(s.T(), e2e.Config.App.Hostname, map[string]string{
		e2e.DevstackLabelHeader:     e2e.DevServeHeader,
		"X-XSRF-TOKEN":              decodeXsrf,
		"Cookie":                    "rzp_usr_session=" + session,
		"apollographql-client-name": "frontend-auth",
	})

	userOnly := true

	response := e.POST("/user/signin").
		WithJSON(&LoginSignUpUserRequest{
			Email:    "userWithMultipleMerchants@razorpay.com",
			Password: "1p1p1p1p1p@",
			Captcha:  "Faked",
			UserOnly: &userOnly,
		}).
		Expect()

	// Since there are multiple merchants for the user, login is unsuccessful and currentMerchantId is not present in the response
	// And the request is coming from USL
	response.JSON().Object().Path("$.data").Object().NotContainsKey("currentMerchantId")
}

func TestTopf(t *testing.T) {
	suite.Run(t, &TopfAPISuite{Suite: itf.NewSuite(itf.WithTags([]string{}), itf.WithPriority(itf.PriorityP0))})
}
