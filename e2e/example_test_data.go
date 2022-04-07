package e2e

import (
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
)

var requestTestForHealth = map[string]*httpexpect.RequestTest{
	"ValidRequest": {
		Method:               http.MethodGet,
		Path:                 "/status",
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: &HealthResponse{
			Redis: "ok",
			API:   "ok",
		},
	},
}

type HealthResponse struct {
	Redis string `json:"redis"`
	API   string `json:"api"`
}
