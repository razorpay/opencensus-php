package virtualaccount

type OrderRequest struct {
	Amount         string `json:"amount"`
	Currency       string `json:"currency"`
	Receipt        string `json:"receipt"`
	PaymentCapture string `json:"payment_capture"`
	AppOffer       bool `json:"app_offer"`
	Discount       bool `json:"discount"`
	ForceOffer     bool `json:"force_offer"`
	PartialPayment bool `json:"partial_payment"`
}

