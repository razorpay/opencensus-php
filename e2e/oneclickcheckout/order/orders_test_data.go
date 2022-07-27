package order

import "github.com/razorpay/api/e2e/oneclickcheckout"

var (
	TestReceipt         = "1234"
	TestCurrency        = "INR"
	TestAmount          = int64(50000)
	TestLineItemsTotal  = int64(50000)
	PaymentCapture      = 1
	TestCustomerId      = "cust_100000customer"
	TestCustomerContact = "+918951468289"
	TestCustomerEmail   = "sangeeta.ng@razorpay.com"
)

type OrderCreateTest struct {
	Name     string
	Request  *OrdersRequest
	Response *OrdersResponse
	Error    error
}
type OrderUpdateTest struct {
	Name    string
	OrderId string
	Request *UpdateCustomerDetailRequest
	Error   *oneclickcheckout.ErrorResponse
}

type OrderFetchTest struct {
	Name     string
	OrderId  string
	Response *OrdersResponse
	Error    error
}

type ResetOrderTest struct {
	Name    string
	OrderId string
	Error   error
}

func OrderCreateSuccessRequest() *OrdersRequest {
	return &OrdersRequest{
		Amount:         TestAmount,
		Currency:       TestCurrency,
		Receipt:        TestReceipt,
		LineItemsTotal: TestLineItemsTotal,
		PaymentCapture: PaymentCapture,
		Note:           &Notes{NotesKey: "Book 1"},
		LineItem:       []*LineItem{},
	}
}

func OrderCreateSuccessResponse() *OrdersResponse {
	return &OrdersResponse{
		Amount:         TestAmount,
		Currency:       TestCurrency,
		Receipt:        TestReceipt,
		LineItemsTotal: TestLineItemsTotal,
	}
}

func UpdateCustomerContactDetailRequest() *UpdateCustomerDetailRequest {
	return &UpdateCustomerDetailRequest{
		CustomerDetail: &CustomerDetail{
			Contact: TestCustomerContact,
			Email:   TestCustomerEmail,
		},
	}
}
func UpdateCustomerAddressDetailRequest(zipcode string) *UpdateCustomerDetailRequest {
	return &UpdateCustomerDetailRequest{
		CustomerDetail: &CustomerDetail{
			Contact: TestCustomerContact,
			Email:   TestCustomerEmail,
			ShippingAddress: &Address{
				Zipcode: zipcode,
				Country: "in",
				City:    "Bengaluru",
				State:   "Karnataka",
				Line1:   "123",
				Line2:   "Whitefield",
				Name:    "Dev tester",
				Type:    "shipping_address",
			},
			BillingAddress: &Address{
				Zipcode: zipcode,
				Country: "in",
				City:    "Bengaluru",
				State:   "Karnataka",
				Line1:   "123",
				Line2:   "Whitefield",
				Name:    "Dev tester",
				Type:    "billing_address",
			},
		},
	}
}

func OrderUpdateCustomerContactDetailResponse() *OrdersResponse {
	return &OrdersResponse{
		CustomerDetail: &CustomerDetail{
			Contact: TestCustomerContact,
			Email:   TestCustomerEmail,
		},
	}
}

func OrderUpdateCustomerAddressDetailResponse(orderAmount int64) *OrdersResponse {
	return &OrdersResponse{
		Amount: orderAmount,
		CustomerDetail: &CustomerDetail{
			Contact: TestCustomerContact,
			Email:   TestCustomerEmail,
			ShippingAddress: &Address{
				Zipcode: "560001",
				Country: "in",
				City:    "Bengaluru",
				State:   "Karnataka",
				Line1:   "123",
				Line2:   "Whitefield",
				Name:    "Dev tester",
				Type:    "shipping_address",
			},
			BillingAddress: &Address{
				Zipcode: "560001",
				Country: "in",
				City:    "Bengaluru",
				State:   "Karnataka",
				Line1:   "123",
				Line2:   "Whitefield",
				Name:    "Dev tester",
				Type:    "billing_address",
			},
		},
	}
}

func AfterResetOrderResponse() *OrdersResponse {
	return &OrdersResponse{
		CodFee:      0,
		ShippingFee: 0,
	}
}
