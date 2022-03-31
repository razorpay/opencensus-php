package transfer

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
	"testing"
)

var transferHost *httpexpect.Expect
var header map[string]string

func Initialize(t *testing.T) {
	transferHost = httpexpect.New(t, e2e.Config.App.Hostname)
	header=map[string]string{
		"X-Dashboard-User-id":   e2e.Config.SubMerchant.User,
		"X-Dashboard-User-Role": e2e.Config.SubMerchant.Role,
		"Content-Type":          "application/json",
	}
}
