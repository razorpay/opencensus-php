package order

type OrdersResponse struct {
	LineItemsTotal int64           `json:"line_items_total,omitempty"`
	ID             string          `json:"id,omitempty"`
	Entity         string          `json:"entity,omitempty"`
	Amount         int64           `json:"amount,omitempty"`
	AmountPaid     int64           `json:"amount_paid,omitempty"`
	AmountDue      int64           `json:"amount_due,omitempty"`
	Currency       string          `json:"currency,omitempty"`
	Receipt        string          `json:"receipt,omitempty"`
	OfferID        interface{}     `json:"offer_id,omitempty"`
	Status         string          `json:"status,omitempty"`
	Attempts       int64           `json:"attempts,omitempty"`
	Notes          []interface{}   `json:"notes,omitempty"`
	CreatedAt      int64           `json:"created_at,omitempty"`
	CustomerDetail *CustomerDetail `json:"customer_details,omitempty"`
	CodFee         int64           `json:"cod_fee,omitempty"`
	ShippingFee    int64           `json:"shipping_fee,omitempty"`
}
