package payments

type PaymentCreateResponse struct {
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

type PaymentFetchResponse struct {
	entity string `json:"entity"`
	count  uint64 `json:"count"`
}

type PaymentFetchObject struct {
	Id             string `json:"id"`
	Entity         string `json:"entity"`
	amount         uint64 `json:"amount"`
	currency       string `json:"currency"`
	status         string `json:"status"`
	orderId        string `json:"order_id"`
	CreatedAt      int64  `json:"created_at"`
	invoiceId      string `json:"invoice_id""`
	terminalId     string `json:"terminal_id"`
	lateAuthorized string `json:"late_authorized"`
	international  string `json:"international"`
	method         string `json:"method"`
	amountRefunded uint64 `json:"amount_refunded"`
	refundStatus   string `json:"refund_status"`
	captured       bool   `json:"captured"`
	description    string `json:"description"`
	cardId         string `json:"card_id"`
	bank           string `json:"bank"`
}
