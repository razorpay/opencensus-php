package transfer

type CreateDirectTransferResponse struct {
	ID                    string        `json:"id"`
	Entity                string        `json:"entity"`
	TransferStatus        string        `json:"transfer_status"`
	SettlementStatus      interface{}   `json:"settlement_status"`
	Source                string        `json:"source"`
	Recipient             string        `json:"recipient"`
	Amount                int           `json:"amount"`
	Currency              string        `json:"currency"`
	AmountReversed        int           `json:"amount_reversed"`
	Notes                 []interface{} `json:"notes"`
	Fees                  int           `json:"fees"`
	Tax                   int           `json:"tax"`
	OnHold                bool          `json:"on_hold"`
	OnHoldUntil           interface{}   `json:"on_hold_until"`
	RecipientSettlementID interface{}   `json:"recipient_settlement_id"`
	CreatedAt             int           `json:"created_at"`
	LinkedAccountNotes    []interface{} `json:"linked_account_notes"`
	ProcessedAt           interface{}   `json:"processed_at"`
	Error                 struct {
		Code        interface{} `json:"code"`
		Description interface{} `json:"description"`
		Field       interface{} `json:"field"`
		Source      interface{} `json:"source"`
		Step        interface{} `json:"step"`
		Reason      interface{} `json:"reason"`
	} `json:"error"`
}