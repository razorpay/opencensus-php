package preference

type PreferencesResponse struct {
	Order *Order `json:"order,omitempty"`
}

type Order struct {
	LineItemsTotal int64  `json:"line_items_total"`
	Amount         int64  `json:"amount"`
	AmountPaid     int64  `json:"amount_paid"`
	AmountDue      int64  `json:"amount_due"`
	Currency       string `json:"currency"`
}
