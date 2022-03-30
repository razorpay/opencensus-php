package virtualaccount

type OrderResponse struct {
	ID         string        `json:"id"`
	Entity     string        `json:"entity"`
	Amount     int           `json:"amount"`
	AmountPaid int           `json:"amount_paid"`
	AmountDue  int           `json:"amount_due"`
	Currency   string        `json:"currency"`
	Receipt    string        `json:"receipt"`
	OfferID    interface{}   `json:"offer_id"`
	Status     string        `json:"status"`
	Attempts   int           `json:"attempts"`
	Notes      []interface{} `json:"notes"`
	CreatedAt  int           `json:"created_at"`
}
