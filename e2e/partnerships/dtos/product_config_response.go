package dtos

type ProductConfigResponse struct {
	Id                     string        `json:"id"`
	AccountId              string        `json:"account_id"`
	ProductName            string        `json:"product_name"`
	ActivationStatus       string        `json:"activation_status"`
	RequestedConfiguration interface{}   `json:"requested_configuration"`
	ActiveConfiguration    Configuration `json:"active_configuration"`
	Requirements           []Requirement `json:"requirements"`
	Tnc                    interface{}   `json:"tnc"`
}

type Configuration struct {
	Methods        Methods        `json:"methods"`
	PaymentCapture PaymentCapture `json:"payment_capture"`
	Checkout       Checkout       `json:"checkout"`
	Refund         Refund         `json:"refund"`
	Notifications  Notifications  `json:"notifications"`
	Settlements    Settlements    `json:"settlements"`
	Otp            interface{}    `json:"otp"`
}

type PaymentCapture struct {
	Mode                  string `json:"mode"`
	AutomaticExpiryPeriod int    `json:"automatic_expiry_period"`
	ManualExpiryPeriod    int    `json:"manual_expiry_period"`
	RefundSpeed           string `json:"refund_speed"`
}

type Requirement struct {
	FieldReference string `json:"field_reference"`
	ResolutionUrl  string `json:"resolution_url"`
	Status         string `json:"status"`
	ReasonCode     string `json:"reason_code"`
}
