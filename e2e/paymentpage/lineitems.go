package e2e
type LineItem struct {
		PaymentPageItemID string `json:"payment_page_item_id"`
		Amount            int    `json:"amount"`
		Quantity          int    `json:"quantity,omitempty"`
}
