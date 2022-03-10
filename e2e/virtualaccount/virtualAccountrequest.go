package virtualaccount

type VirtualAccountRequest struct {
	Receiver *ReceiversRequest `json:"receivers,omitempty"`
	Name           string `json:"name,omitempty"`
	AmountExpected string `json:"amount_expected,omitempty"`
	CustomerID     string `json:"customer_id,omitempty"`
	Description    string `json:"description,omitempty"`
	CloseBy        int `json:"close_by,omitempty"`
}

type ReceiversRequest struct {
	Types []string `json:"types,omitempty"`
	BankAccount interface {
	} `json:"bank_account,omitempty"`
}


