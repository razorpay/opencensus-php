package qrcodes

var CreateQrCodesPositiveTests = []struct {
	description string
	req         QrCodeCreateRequest
}{
	{
		description: "UPI QR code create, no fixed amount, Multiple use",
		req: QrCodeCreateRequest{
			Usage:       "multiple_use",
			Type:        "upi_qr",
			Name:        "Test UPI QR",
			Description: "This is a test QR code",
			FixedAmount: false,
			CloseBy:     1681615838,
		},
	},
	{
		description: "UPI QR code create, fixed amount, Multiple use",
		req: QrCodeCreateRequest{
			Usage:         "multiple_use",
			Type:          "upi_qr",
			Name:          "Test UPI QR",
			Description:   "This is a test QR code",
			FixedAmount:   true,
			PaymentAmount: 1000,
			CloseBy:       1681615838,
		},
	},
	{
		description: "UPI QR code create, fixed amount, single use",
		req: QrCodeCreateRequest{
			Usage:         "single_use",
			Type:          "upi_qr",
			Name:          "Test UPI QR",
			Description:   "This is a test QR code",
			FixedAmount:   true,
			PaymentAmount: 1000,
			CloseBy:       1681615838,
		},
	},
	{
		description: "QR code with customer",
		req: QrCodeCreateRequest{
			Usage:         "single_use",
			Type:          "upi_qr",
			Name:          "Test UPI QR",
			Description:   "This is a test QR code",
			CustomerId:    "cust_CuJcM0RDVAylfm",
			FixedAmount:   true,
			PaymentAmount: 1000,
			CloseBy:       1681615838,
		},
	},
}

var CreateQrCodesNegativeTests = []struct {
	description   string
	req           QrCodeCreateRequest
	errorResponse ErrorResponse
}{
	{
		description: "Invalid Type",
		req: QrCodeCreateRequest{
			Usage:       "multiple_use",
			Type:        "upi",
			Name:        "Test UPI QR",
			Description: "This is a test QR code",
			FixedAmount: false,
			CloseBy:     1681615838,
		},
		errorResponse: ErrorResponse{
			Error: &Error{
				Code:        "BAD_REQUEST_ERROR",
				Description: "The selected type is invalid.",
				Source:      "business",
				Step:        "payment_initiation",
				Reason:      "input_validation_failed",
				Field:       "type",
			},
		},
	},
	{
		description: "Invalid usage",
		req: QrCodeCreateRequest{
			Usage:       "multiple",
			Type:        "upi_qr",
			Name:        "Test UPI QR",
			Description: "This is a test QR code",
			FixedAmount: false,
			CloseBy:     1681615838,
		},
		errorResponse: ErrorResponse{
			Error: &Error{
				Code:        "BAD_REQUEST_ERROR",
				Description: "The selected usage is invalid.",
				Source:      "business",
				Step:        "payment_initiation",
				Reason:      "input_validation_failed",
				Field:       "usage",
			},
		},
	},
	{
		description: "Payment amount not present for fixed amount true",
		req: QrCodeCreateRequest{
			Usage:       "multiple_use",
			Type:        "upi_qr",
			Name:        "Test UPI QR",
			Description: "This is a test QR code",
			FixedAmount: true,
			CloseBy:     1681615838,
		},
		errorResponse: ErrorResponse{
			Error: &Error{
				Code:        "BAD_REQUEST_ERROR",
				Description: "The payment amount field is required when fixed amount is true.",
				Source:      "business",
				Step:        "payment_initiation",
				Reason:      "input_validation_failed",
				Field:       "payment_amount",
			},
		},
	},
	{
		description: "Invalid Close by",
		req: QrCodeCreateRequest{
			Usage:       "multiple_use",
			Type:        "upi_qr",
			Name:        "Test UPI QR",
			Description: "This is a test QR code",
			FixedAmount: false,
			CloseBy:     1647419980,
		},
		errorResponse: ErrorResponse{
			Error: &Error{
				Code:        "BAD_REQUEST_ERROR",
				Description: "close_by should be at least 2 minutes after current time",
				Source:      "business",
				Step:        "payment_initiation",
				Reason:      "input_validation_failed",
			},
		},
	},
}

var FetchQrCodesTests = []struct {
	description    string
	createRequests []QrCodeCreateRequest
	fetchRequest   QrCodeFetchRequest
	fetchResponse  QrCodeFetchResponse
}{
	{
		description:    "Search by name",
		createRequests: []QrCodeCreateRequest{},
		fetchRequest: QrCodeFetchRequest{
			Name: "megastore",
		},
		fetchResponse: QrCodeFetchResponse{
			Entity: "collection",
			Count:  10,
		},
	},
	{
		description:    "Search by Customer name",
		createRequests: []QrCodeCreateRequest{},
		fetchRequest: QrCodeFetchRequest{
			CustomerName: "Gaurav",
		},
		fetchResponse: QrCodeFetchResponse{
			Entity: "collection",
			Count:  10,
		},
	},
	{
		description:    "Search by Customer contact",
		createRequests: []QrCodeCreateRequest{},
		fetchRequest: QrCodeFetchRequest{
			CustomerContact: "9123456780",
		},
		fetchResponse: QrCodeFetchResponse{
			Entity: "collection",
			Count:  10,
		},
	},
	{
		description:    "Search by Customer email",
		createRequests: []QrCodeCreateRequest{},
		fetchRequest: QrCodeFetchRequest{
			CustomerEmail: "gaurav.kumar@example.com",
		},
		fetchResponse: QrCodeFetchResponse{
			Entity: "collection",
			Count:  10,
		},
	},
}

var QrPaymentSharpPos = []struct {
	description string
	createReq   QrCodeCreateRequest
	payRequest  QrPaymentRequestSharp
}{
	{
		description: "Upi Payment",
		createReq: QrCodeCreateRequest{
			Usage:       "multiple_use",
			Type:        "upi_qr",
			Name:        "Test UPI QR",
			Description: "This is a test QR code",
			FixedAmount: false,
			CloseBy:     1681615838,
		},
		payRequest: QrPaymentRequestSharp{
			Method: "upi",
			Amount: "100",
		},
	},
}
