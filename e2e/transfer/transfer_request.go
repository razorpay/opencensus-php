package transfer

type CreateDirectTransferRequest struct{
	Account string `json:"account"`
	Amount int `json:"amount"`
	Currency string `json:"currency"`
}
