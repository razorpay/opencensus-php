package virtualaccount

type VirtualAccountResponse struct {
	ID             string        `json:"id"`
	Name           string        `json:"name"`
	Entity         string        `json:"entity"`
	Status         string        `json:"status"`
	Description    string        `json:"description"`
	AmountExpected int           `json:"amount_expected"`
	Notes          []interface{} `json:"notes"`
	AmountPaid     int           `json:"amount_paid"`
	CustomerID     string        `json:"customer_id"`
	Receivers[] Receivers  `json:"receivers"`
	CloseBy   interface{} `json:"close_by"`
	ClosedAt  interface{} `json:"closed_at"`
	CreatedAt int         `json:"created_at"`
}

type Receivers struct {
	ID            string        `json:"id"`
	Entity        string        `json:"entity"`
	Ifsc          string        `json:"ifsc"`
	BankName      string        `json:"bank_name"`
	Name          string        `json:"name"`
	Notes         []interface{} `json:"notes"`
	AccountNumber string        `json:"account_number"`
}
