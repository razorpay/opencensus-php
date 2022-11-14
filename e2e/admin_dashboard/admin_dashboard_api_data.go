package e2e

import (
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
)

var requestTestGetIndex = map[string]*httpexpect.RequestTest{
	VALID_REQUEST: {
		Method:               http.MethodGet,
		Path:                 PATH_ADMIN,
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: nil,
	},
}
