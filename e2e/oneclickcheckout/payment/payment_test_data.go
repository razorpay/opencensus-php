package payment

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/api/e2e/oneclickcheckout"
	"github.com/razorpay/api/e2e/oneclickcheckout/order"
)

var (
	TestBank          = "IBKL"
	TestDescription   = "Fine tshirt"
	TestPaymentMethod = "netbanking"
)

type CreatePaymentTest struct {
	Name     string
	Request  *CreatePaymentRequest
	Response *CreateCodPaymentResponse
	Error    *oneclickcheckout.ErrorResponse
}

type CreateNonCodPaymentTest struct {
	Name     string
	Request  *CreatePaymentRequest
	Response *CreateNonCodPaymentResponse
	Error    *oneclickcheckout.ErrorResponse
}

func CreatePaymentTestRequest(orderAmount int64, paymentMethod string, orderId string) *CreatePaymentRequest {
	return &CreatePaymentRequest{
		Contact:     order.TestCustomerContact,
		Email:       order.TestCustomerEmail,
		Amount:      orderAmount,
		Method:      paymentMethod,
		Bank:        TestBank,
		Currency:    order.TestCurrency,
		Description: TestDescription,
		OrderID:     orderId,
		KeyID:       e2e.Config.OneClickCheckout.Username,
	}
}

func CreateCodPaymentTestResponse(orderID string) *CreateCodPaymentResponse {
	return &CreateCodPaymentResponse{
		RazorpayOrderID: orderID,
	}
}

func CreateNonCodPaymentTestResponse(orderAmount int64) *CreateNonCodPaymentResponse {
	return &CreateNonCodPaymentResponse{
		Request: &Request{
			Content: &Content{
				Amount: orderAmount,
				Method: TestPaymentMethod,
			},
		},
	}
}

func GetErrorForPaymentAmountMismatch() *oneclickcheckout.ErrorResponse {
	return &oneclickcheckout.ErrorResponse{
		Error: &oneclickcheckout.Error{
			Code:        "BAD_REQUEST_ERROR",
			Description: "Your payment amount is different from your order amount. To pay successfully, please try using right amount.",
			Source:      "business",
			Step:        "payment_initiation",
			Reason:      "input_validation_failed",
			Metadata:    struct{}{},
			Field:       "amount",
		},
	}
}

func GetErrorForAmountTampered() *oneclickcheckout.ErrorResponse {
	return &oneclickcheckout.ErrorResponse{
		Error: &oneclickcheckout.Error{
			Code:        "BAD_REQUEST_ERROR",
			Description: "Payment failed because fees or tax was tampered",
			Source:      "business",
			Step:        "payment_initiation",
			Reason:      "input_validation_failed",
			Metadata:    struct{}{},
			Field:       "fee",
		},
	}
}
