package e2e

type LinkedAccountCreateRequest struct {
	Name           string      `json:"name"`
	Email          interface{} `json:"email"`
	TncAccepted    bool        `json:"tnc_accepted"`
	AccountDetails struct {
		BussinessName string `json:"business_name"`
		BussinessType string `json:"business_type"`
	} `json:"account_details"`
	BankAccount struct {
		IfscCode        string `json:"ifsc_code"`
		BeneficiaryName string `json:"beneficiary_name"`
		AccountType     string `json:"account_type"`
		AccountNumber   string `json:"account_number"`
	} `json:"bank_account"`
}
