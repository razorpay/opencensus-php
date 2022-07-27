package preference

import (
	"testing"

	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
)

var oneClickCheckoutHost *httpexpect.Expect
var header map[string]string
var query map[string]string

func Initialize(t *testing.T) {
	oneClickCheckoutHost = httpexpect.New(t, e2e.Config.App.Hostname)
	header = map[string]string{
		"Content-Type": "application/json",
	}
	query = map[string]string{
		"key_id":            e2e.Config.OneClickCheckout.Username,
		"[agent]{device]":   "desktop",
		"[agent]{os]":       "macOS",
		"[agent]{platform]": "web",
		"[build]":           "2165785880",
		"[checkout_id]":     "JJCM5rm6VMg8FJ",
		"[library]":         "checkoutjs",
		"[platform]":        "browser",
		"[request_index]":   "0",
		"amount":            "100",
		"checkcookie":       "1",
		"currency[0]":       "INR",
		"personalisation":   "1",
	}
}
