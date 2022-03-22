package qrcodes

type QrCodeCreateRequest struct {
	Name          string `json:"name,omitempty"`
	CloseBy       int64  `json:"close_by,omitempty"`
	Usage         string `json:"usage"`
	Description   string `json:"description,omitempty"`
	Type          string `json:"type"`
	FixedAmount   bool   `json:"fixed_amount"`
	PaymentAmount int64  `json:"payment_amount,omitempty"`
	CustomerId    string `json:"customer_id,omitempty"`
}
