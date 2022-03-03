package e2e

import (
	"encoding/json"
	"fmt"
	"github.com/razorpay/api/e2e"
	"net/http"
	"testing"
)


// Create PaymentPage
func CreatePaymentPage(t *testing.T, paymentPageReq PaymentPageRequest) PaymentPageResponse {
	var paymentPageRes PaymentPageResponse
	Initialize(t)
	obj := paymentPageHost.POST("/v1/payment_pages").
		WithBasicAuth(e2e.Config.PaymentPage.Username, e2e.Config.PaymentPage.Password).
		WithHeaders(header).
		WithJSON(paymentPageReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &paymentPageRes)
	return paymentPageRes
}

func CreateSubsctiptionButton(t *testing.T, paymentPageReq PaymentPageRequest) PaymentPageResponse {
	var paymentPageRes PaymentPageResponse
	Initialize(t)
	obj := paymentPageHost.POST("/v1/payment_pages").
		WithBasicAuth(e2e.Config.PaymentPage.Username, e2e.Config.PaymentPage.Password).
		WithHeaders(header).
		WithQuery("view_type","subscription_button").
		WithJSON(paymentPageReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &paymentPageRes)
	return paymentPageRes
}

// Create PaymentPageOrder
func CreatePaymentPageOrder(t *testing.T, paymentPageOrderReq PaymentPageOrderRequest,paymentPageRes PaymentPageResponse) PaymentPageOrderResponse {
	Initialize(t)
	var ppOrderRes PaymentPageOrderResponse
	lineitems := paymentPageOrderReq.LineItems
	line := LineItem{
		PaymentPageItemID: paymentPageRes.PaymentPageItems[0].ID,
		Amount:            paymentPageRes.PaymentPageItems[0].Item.Amount,
		Quantity:          1,
	}
	liness := append(lineitems, line)
	paymentPageOrderReq.LineItems = liness
	obj := paymentPageHost.POST(fmt.Sprintf("/v1/payment_pages/%s/order",paymentPageRes.ID)).
		WithBasicAuth(e2e.Config.PaymentPage.Username, e2e.Config.PaymentPage.Password).
		WithHeader("Content-Type","application/json").
		WithJSON(paymentPageOrderReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &ppOrderRes)
	return ppOrderRes
}

func UpdatePaymentPage(t *testing.T, paymentPageReq PaymentPageRequest,paymentPageRes PaymentPageResponse) PaymentPageResponse {
	Initialize(t)
	var ppRes PaymentPageResponse
	obj := paymentPageHost.PATCH(fmt.Sprintf("/v1/payment_pages/%s",paymentPageRes.ID)).
		WithBasicAuth(e2e.Config.PaymentPage.Username, e2e.Config.PaymentPage.Password).
		WithHeaders(header).
		WithJSON(paymentPageReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &ppRes)
	return ppRes
}

func CreatePaymentPageOrderNegative(t *testing.T, paymentPageOrderReq PaymentPageOrderRequest,paymentPageRes PaymentPageResponse) ErrorResponse {
	Initialize(t)
	var ppOrderRes ErrorResponse

	obj := paymentPageHost.POST(fmt.Sprintf("/v1/payment_pages/%s/order",paymentPageRes.ID)).
		WithBasicAuth(e2e.Config.PaymentPage.Username, e2e.Config.PaymentPage.Password).
		WithHeader("Content-Type","application/json").
		WithJSON(paymentPageOrderReq).
		Expect().
		Status(http.StatusBadRequest).Body()
	json.Unmarshal([]byte(obj.Raw()), &ppOrderRes)
	return ppOrderRes
}
