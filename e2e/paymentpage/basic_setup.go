package e2e

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
	"testing"
)

var paymentPageHost *httpexpect.Expect
var header map[string]string

func Initialize(t *testing.T) {
	paymentPageHost = httpexpect.New(t, e2e.Config.App.Hostname)
	header=map[string]string{
		"X-Dashboard-User-id":   e2e.Config.PaymentPage.User,
		"X-Dashboard-User-Role": e2e.Config.PaymentPage.Role,
		"Content-Type":          "application/json",
	}
}
