package payments

type Note struct {
	merchant_order_id string `json:"merchant_order_id,omitempty"`
}

type CardDetails struct {
	number       uint64 `json:"number,omitempty"`
	name         string `json:"name,omitempty"`
	expiry_month int16  `json:"expiry_month,omitempty"`
	expiry_year  int16  `json:"expiry_year,omitempty"`
	cvv          int16  `json:"cvv,omitempty"`
}

type PaymentCreateRequest struct {
	amount      int16       `json:"amount,omitempty"`
	currency    string      `json:"currency,omitempty"`
	email       string      `json:"usage,omitempty"`
	contact     uint64      `json:"description,omitempty"`
	notes       Note        `json:"notes,omitempty"`
	description string      `json:"description,omitempty"`
	bank        string      `json:"bank,omitempty"`
	card        CardDetails `json:"card,omitempty"`
	//_           string      `json:"_"`
	//vpa    string `json:"vpa"`
	//method string `json:"method"`
}
