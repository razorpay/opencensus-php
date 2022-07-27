package preference

import (
	"net/http"
	"testing"

	"github.com/razorpay/api/e2e"
)

func GetPreferences(t *testing.T, orderId string) []byte {
	Initialize(t)
	query["order_id"] = orderId
	obj := oneClickCheckoutHost.GET("/v1/preferences").
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithHeaders(header).
		WithQueryObject(query).
		Expect().
		Status(http.StatusOK).Body()
	return []byte(obj.Raw())
}
