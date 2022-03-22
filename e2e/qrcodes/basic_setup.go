package qrcodes

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
	"testing"
)

var qrCodesHost *httpexpect.Expect
var header map[string]string

func Initialize(t *testing.T) {
	qrCodesHost = httpexpect.New(t, e2e.Config.App.Hostname)
	header=map[string]string{
		"Content-Type":          "application/json",
	}
}
