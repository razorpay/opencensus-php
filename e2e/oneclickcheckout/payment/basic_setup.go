package payment

import (
	"testing"

	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
)

var oneClickCheckoutHost *httpexpect.Expect
var header map[string]string

func Initialize(t *testing.T) {
	oneClickCheckoutHost = httpexpect.New(t, e2e.Config.App.Hostname)
	header = map[string]string{
		"Content-Type": "application/json",
	}
}
