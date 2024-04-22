package e2e

const (
	DEVSTACK_LABEL_HEADER     = "rzpctx-dev-serve-user"
	HEADER_X_XSRF_TOKEN       = "X-XSRF-TOKEN"
	HEADER_CONTENT_TYPE       = "Content-Type"
	APPLICATION_JSON          = "application/json"
	VALID_REQUEST             = "ValidRequest"
	COOKIE_XSRF_TOKEN         = "XSRF-TOKEN"
	COOKIE_RZP_USR_SESSION    = "rzp_usr_session"
	COOKIE_RZP_ACCESS_TOKEN   = "rzp_access_token"
	RZP_USER_SESSION          = "rzp_usr_session"
	COOKIE                    = "Cookie"
	ORIGIN                    = "origin"
	EMAIL                     = "email"
	DEVSTACK_TEST_EMAIL       = "piyush.verma+35015@razorpay.com"
	DEVSTACK_LA_TEST_EMAIL    = "praveen.patlola+editaccount@razorpay.com"
	PASSWORD                  = "password"
	DEVSTACK_TEST_PASSWORD    = "Pi123456"
	DEVSTACK_LA_TEST_PASSWORD = "test1234"
	CAPTCHA                   = "captcha"
	DEVSTACK_TEST_CAPTCHA     = "Faked"
	POST_METHOD               = "POST"
	GET_METHOD                = "GET"
	PATH_APP_DASHBOARD        = "/app/dashboard"
	PATH_ORG                  = "/org"
	PATH_USER_SIGNIN          = "/user/signin"
	PATH_USER_LOGOUT          = "/user/logout"
	DASHBOARD_ORIGIN          = "https://dashboard.dev.razorpay.in"
)

var dashboardTestCreds = map[string]string{
	EMAIL:    DEVSTACK_TEST_EMAIL,
	PASSWORD: DEVSTACK_TEST_PASSWORD,
	CAPTCHA:  DEVSTACK_TEST_CAPTCHA,
}

var dashboardLATestCreds = map[string]string{
	EMAIL:    DEVSTACK_LA_TEST_EMAIL,
	PASSWORD: DEVSTACK_LA_TEST_PASSWORD,
	CAPTCHA:  DEVSTACK_TEST_CAPTCHA,
}

var AccessTokenCreds = map[string]string{
	EMAIL:    DEVSTACK_TEST_EMAIL,
	PASSWORD: DEVSTACK_TEST_PASSWORD,
	CAPTCHA:  DEVSTACK_TEST_CAPTCHA,
}

var componentsToAssert = []string{
	`<title>Razorpay Dashboard</title>`,
	`<meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />`,
}
