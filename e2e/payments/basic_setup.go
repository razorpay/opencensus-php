package payments

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
	"testing"
)

var paymentsHost *httpexpect.Expect
var header map[string]string

func Initialize(t *testing.T) {
	paymentsHost = httpexpect.New(t, e2e.Config.App.Hostname)
	header = map[string]string{
		"Content-Type": "application/json",
	}
}
