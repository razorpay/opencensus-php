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

type QrCodeFetchRequest struct {
	Name            string `json:"name,omitempty"`
	CustomerId      string `json:"customer_id,omitempty"`
	Status          string `json:"status,omitempty"`
	CustomerName    string `json:"cust_name,omitempty"`
	CustomerEmail   string `json:"cust_email,omitempty"`
	CustomerContact string `json:"cust_contact,omitempty"`
}

type QrPaymentRequestSharp struct {
	Reference string `json:"reference"`
	Method    string `json:"method"`
	Amount    string `json:"amount"`
}
