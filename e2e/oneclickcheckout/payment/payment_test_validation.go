package payment

import (
	"encoding/json"
	"testing"

	"github.com/razorpay/api/e2e/oneclickcheckout"
)

func TestCreatePayment(t *testing.T, createPaymentTest CreatePaymentTest) CreatePaymentTest {
	createPaymentRes := CreatePayment(t, *createPaymentTest.Request)
	var actualRes CreateCodPaymentResponse
	if createPaymentTest.Response != nil {
		expected, _ := json.Marshal(createPaymentTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, createPaymentRes)
		json.Unmarshal(createPaymentRes, &actualRes)
		createPaymentTest.Response = &actualRes
	}
	return createPaymentTest
}

func TestCreateNonCodPayment(t *testing.T, createNonCodPaymentTest CreateNonCodPaymentTest) CreateNonCodPaymentTest {
	createNonCodPaymentRes := CreatePayment(t, *createNonCodPaymentTest.Request)
	var actualRes CreateNonCodPaymentResponse
	if createNonCodPaymentTest.Response != nil {
		expected, _ := json.Marshal(createNonCodPaymentTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, createNonCodPaymentRes)
		json.Unmarshal(createNonCodPaymentRes, &actualRes)
		createNonCodPaymentTest.Response = &actualRes
	} else if createNonCodPaymentTest.Error != nil {
		expected, _ := json.Marshal(createNonCodPaymentTest.Error)
		oneclickcheckout.ValidateResponse(t, expected, createNonCodPaymentRes)
	}
	return createNonCodPaymentTest
}
