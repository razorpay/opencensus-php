package payment

type CreatePaymentRequest struct {
	Contact     string `json:"contact"`
	Email       string `json:"email"`
	Amount      int64  `json:"amount"`
	Method      string `json:"method"`
	Bank        string `json:"bank"`
	Currency    string `json:"currency"`
	Description string `json:"description"`
	OrderID     string `json:"order_id"`
	KeyID       string `json:"key_id"`
}
