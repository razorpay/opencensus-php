package order

import (
	"encoding/json"
	"testing"

	"github.com/razorpay/api/e2e/oneclickcheckout"
)

func TestOrderCreation(t *testing.T, orderCreateTest OrderCreateTest) OrderCreateTest {
	orderRes := CreateOrders(t, *orderCreateTest.Request)
	var actualRes OrdersResponse
	if orderCreateTest.Response != nil {
		expected, _ := json.Marshal(orderCreateTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, orderRes)
		//if actual and expected responsees are matching add the actual order ID, created_at
		json.Unmarshal(orderRes, &actualRes)
		orderCreateTest.Response = &actualRes
	}
	//if invalid scenario test case, we have to set error in the orderCreateTest and pass back.
	return orderCreateTest
}

func TestCustomerDetailUpdate(t *testing.T, orderUpdateTest OrderUpdateTest) {
	res := UpdateCustomerDetails(t, *orderUpdateTest.Request, orderUpdateTest.OrderId)
	if orderUpdateTest.Error != nil {
		expected, _ := json.Marshal(orderUpdateTest.Error)
		oneclickcheckout.ValidateResponse(t, expected, res)
	}
}

func TestResetOrder(t *testing.T, resetOrderTest ResetOrderTest) {
	ResetOrder(t, resetOrderTest.OrderId)
}

func TestFetchOrderById(t *testing.T, orderFetchTest OrderFetchTest) OrderFetchTest {
	fetchOrderRes := FetchOrderById(t, orderFetchTest.OrderId)
	var actualRes OrdersResponse
	if orderFetchTest.Response != nil {
		expected, _ := json.Marshal(orderFetchTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, fetchOrderRes)
		json.Unmarshal(fetchOrderRes, &actualRes)
		orderFetchTest.Response = &actualRes
	}
	return orderFetchTest
}
