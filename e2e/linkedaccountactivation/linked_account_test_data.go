package linked_account_activation

var CreateLinkedAccountPositiveTestCases = []struct {
	Description string
	Req         LinkedAccountCreateRequest
}{
	{
		Description: "Create Linked Account with valid bank account details",
		Req: LinkedAccountCreateRequest{
			Name:        "Rzp Test QA Merchant",
			TncAccepted: true,
			AccountDetails: AccountDetails{
				BussinessName: "Business",
				BussinessType: "individual"},
			BankAccount: BankAccount{
				IfscCode:        "HDFC0000009",
				BeneficiaryName: "Rzp Test QA Merchant",
				AccountType:     "current",
				AccountNumber:   "1234567890"},
		},
	},
}

var CreateLinkedAccountNegativeTestCases = []struct {
	Description string
	Req         LinkedAccountCreateRequest
}{
	{
		Description: "Create Linked Account with valid bank account details",
		Req: LinkedAccountCreateRequest{
			Name:        "Rzp Test QA Merchant",
			TncAccepted: true,
			AccountDetails: AccountDetails{
				BussinessName: "Business",
				BussinessType: "individual"},
			BankAccount: BankAccount{
				IfscCode:        "HDFC0000009",
				BeneficiaryName: "Rzp Test QA Merchant",
				AccountType:     "current",
				AccountNumber:   "1234567892"},
		},
	},
}

var BankDetailsUpdateRequestData = BankDetailsUpdateRequest{
	BeneficiaryName: "Emma Stone",
	AccountNumber: "1234567893",
	IfscCode: "SBIN0000004",
}