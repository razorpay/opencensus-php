package linked_account_activation

type LinkedAccountCreateRequest struct {
	Name           string         `json:"name"`
	Email          interface{}    `json:"email"`
	TncAccepted    bool           `json:"tnc_accepted"`
	AccountDetails AccountDetails `json:"account_details"`
	BankAccount    BankAccount    `json:"bank_account"`
}

type BankAccount struct {
	IfscCode        string `json:"ifsc_code"`
	BeneficiaryName string `json:"beneficiary_name"`
	AccountType     string `json:"account_type"`
	AccountNumber   string `json:"account_number"`
}

type AccountDetails struct {
	BussinessName string `json:"business_name"`
	BussinessType string `json:"business_type"`
}

type MockBVSValidationEventRequest struct {
	Data MockBvsRequestData `json:"data"`
}

type MockBvsRequestData struct {
	ValidationId     string `json:"validation_id"`
	ErrorCode        string `json:"error_code"`
	ErrorDescription string `json:"error_description"`
	Status           string `json:"status"`
}

type BankDetailsUpdateRequest struct {
	BeneficiaryName string `json:"beneficiary_name"`
	AccountNumber   string `json:"account_number"`
	IfscCode        string `json:"ifsc_code"`
}

type BankDetailsUpdateResponse struct {
	Status          string `json:"status"`
	BeneficiaryName string `json:"beneficiary_name"`
	AccountNumber   string `json:"account_number"`
	IfscCode        string `json:"ifsc_code"`
}