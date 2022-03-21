package virtualaccount

type VirtualAccountEntityResponse struct {
	Entity string `json:"entity"`
	Count int `json:"count"`
	Items[] VirtualAccountResponse `json:"items"`
}

