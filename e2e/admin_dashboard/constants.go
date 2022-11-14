package e2e

const (
	DEVSTACK_LABEL_HEADER             = "rzpctx-dev-serve-user"
	HEADER_X_XSRF_TOKEN               = "X-XSRF-TOKEN"
	HEADER_CONTENT_TYPE               = "Content-Type"
	APPLICATION_X_WWW_FORM_URLENCODED = "application/x-www-form-urlencoded; charset=UTF-8"
	VALID_REQUEST                     = "ValidRequest"
	COOKIE_XSRF_TOKEN                 = "XSRF-TOKEN"
	COOKIE_RZP_USR_SESSION            = "rzp_usr_session"
	RZP_USER_SESSION                  = "rzp_usr_session"
	COOKIE                            = "Cookie"
	DEVSTACK_ADMIN_TEST_EMAIL         = "admine2etest@razorpay.com"
	DEVSTACK_ADMIN_TEST_PASSWORD      = "Test@123"
	PASSWORD                          = "password"
	USERNAME                          = "username"
	POST_METHOD                       = "POST"
	GET_METHOD                        = "GET"
	PATH_ORG                          = "/org"
	PATH_ADMIN                        = "/admin"
	PATH_ADMIN_SIGNIN                 = "/admin/signin"
)

var adminDashboardTestCreds = map[string]string{
	USERNAME: DEVSTACK_ADMIN_TEST_EMAIL,
	PASSWORD: DEVSTACK_ADMIN_TEST_PASSWORD,
}

var componentsToAssertPostLogin = []string{
	`<title>Razorpay - Admin Panel</title>`,
	`<meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />`,
}

var componentsToAssertBeforeLogin = []string{
	`<div class="auth-heading">Admin Login</div>`,
}
