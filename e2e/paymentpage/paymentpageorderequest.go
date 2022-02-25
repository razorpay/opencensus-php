package e2e

type PaymentPageOrderRequest struct {
	LineItems [] LineItem `json:"line_items"`
}
