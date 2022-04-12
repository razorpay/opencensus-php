package qrcodes

type QrCodeCreateResponse struct {
	Id                     string `json:"id"`
	Entity                 string `json:"entity"`
	CreatedAt              int64  `json:"created_at"`
	PaymentsAmountReceived int64  `json:"payments_amount_received"`
	PaymentsCountReceived  int64  `json:"payments_count_received"`
	ImageUrl               string `json:"image_url"`
	Name                   string `json:"name"`
	CustomerId             string `json:"customer_id"`
	CloseBy                int64  `json:"close_by"`
	Usage                  string `json:"usage"`
	Description            string `json:"description"`
	Type                   string `json:"type"`
	Status                 string `json:"status"`
	FixedAmount            bool   `json:"fixed_amount"`
	PaymentAmount          int64  `json:"payment_amount"`
}

type QrCodeFetchResponse struct {
	Entity string                 `json:"entity"`
	Count  int                    `json:"count"`
	Items  []QrCodeCreateResponse `json:"items"`
}

type QrPaymentFetchResponse struct {
	Entity string                 `json:"entity"`
	Count  int                    `json:"count"`
}
