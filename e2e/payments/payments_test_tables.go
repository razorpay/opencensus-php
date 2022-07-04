package payments

var CreatePaymentTests = []struct {
	description string
	req         PaymentCreateRequest
}{
	{
		description: "default payment object creation",
		req: PaymentCreateRequest{
			amount:      10000,
			currency:    "INR",
			email:       "a@b.com",
			contact:     9999999999,
			notes:       Note{merchant_order_id: "random order id"},
			description: "random description himgang",
			bank:        "IDIB",
			card:        CardDetails{number: 4012001038443335, name: "Harshil", expiry_month: 12, expiry_year: 2024, cvv: 566},
			//callback:    "abcdefghijkl",
			//vpa:    "dontencrypt@icici",
			//method: "upi",
		},
	},
}

var FetchPaymentsTests = []struct {
	description    string
	createRequests []PaymentCreateRequest
	payment_id     string
	fetchResponse  PaymentFetchResponse
}{
	{
		description:    "Fetch By Id",
		createRequests: []PaymentCreateRequest{},
		payment_id:     "JcYKoaSWHppzgd",
		fetchResponse:  PaymentFetchResponse{},
	},
}
