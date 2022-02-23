package e2e

type LinkedAccountCreateResponse struct {
	Id                string `json:"id"`
	Entity            string `json:"entity"`
	Name              string `json:"name"`
	Email             string `json:"email"`
	Live              string `json:"live"`
	Managed           string `json:"managed"`
	TncAccepted       string `json:"tnc_accepted"`
	ActivationDetails struct {
		Status                       string `json:"status"`
		ActivatedAt                  int    `json:"activated_at"`
		CanSubmit                    bool   `json:"can_submit"`
		BankDetailsVerificationError string `json:"bank_details_verification_error"`
	} `json:"activation_details"`
}

type MerchantActivationDetails struct {
	ActivationProgress            string `json:activation_progress"`
	ActivationStatus              string `json:"activation_status"`
	BankDetailsVerificationStatus string `json:"bank_details_verification_status"`
}
