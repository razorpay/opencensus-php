package virtualaccount

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
	"testing"
)

var virtualAccountHost *httpexpect.Expect
var header map[string]string

func Initialize(t *testing.T) {
	virtualAccountHost = httpexpect.New(t, e2e.Config.App.Hostname)
	header=map[string]string{
		"Content-Type":          "application/json",
	}
}
