package order

import (
	"fmt"
	"testing"

	"github.com/razorpay/api/e2e"
)

func CreateOrders(t *testing.T, orderReq OrdersRequest) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.POST("/v1/orders").
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithHeaders(header).
		WithJSON(orderReq).
		Expect().
		Body()
	return []byte(obj.Raw())
}

func UpdateCustomerDetails(t *testing.T, updateCustomerDetailsReq UpdateCustomerDetailRequest,
	orderId string) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.PATCH(fmt.Sprintf("/v1/orders/1cc/%s/customer", orderId)).
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithQueryObject(query).
		WithHeaders(header).
		WithJSON(updateCustomerDetailsReq).
		Expect().
		Body()
	return []byte(obj.Raw())
}

func FetchOrderById(t *testing.T, orderId string) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.GET(fmt.Sprintf("/v1/orders/%s", orderId)).
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, e2e.Config.OneClickCheckout.Password).
		WithHeaders(header).
		Expect().
		Body()
	return []byte(obj.Raw())
}

func ResetOrder(t *testing.T, orderId string) []byte {
	Initialize(t)
	obj := oneClickCheckoutHost.POST(fmt.Sprintf("/v1/orders/1cc/%s/reset", orderId)).
		WithBasicAuth(e2e.Config.OneClickCheckout.Username, "").
		WithHeaders(header).
		Expect().
		Body()
	return []byte(obj.Raw())
}
