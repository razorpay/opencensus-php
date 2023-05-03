package dtos

type ProductConfigCreateRequest struct {
	ProductName string `json:"product_name"`
	TncAccepted bool   `json:"tnc_accepted"`
	Ip          string `json:"ip,omitempty"`
}

type ProductConfigRequest struct {
	Checkout      *Checkout      `json:"checkout,omitempty"`
	Refund        *Refund        `json:"refund,omitempty"`
	Notifications *Notifications `json:"notifications,omitempty"`
	Settlements   *Settlements   `json:"settlements,omitempty"`
	TncAccepted   bool           `json:"tnc_accepted,omitempty"`
	Ip            string         `json:"ip,omitempty"`
	Otp           *Otp           `json:"otp,omitempty"`
}

type Methods struct {
	Card       PaymentMethod `json:"card"`
	NetBanking PaymentMethod `json:"netbanking"`
	Emi        PaymentMethod `json:"emi"`
	Wallet     PaymentMethod `json:"wallet"`
	Paylater   PaymentMethod `json:"paylater"`
	Upi        PaymentMethod `json:"upi"`
}

type PaymentMethod struct {
	Enabled    bool      `json:"enabled,omitempty"`
	Instrument *[]string `json:"instrument,omitempty"`
}

type Checkout struct {
	ThemeColor    string `json:"theme_color,omitempty"`
	FlashCheckout bool   `json:"flash_checkout,omitempty"`
}

type Refund struct {
	DefaultRefundSpeed string `json:"default_refund_speed,omitempty"`
}

type Notifications struct {
	Whatsapp bool     `json:"whatsapp,omitempty"`
	Sms      bool     `json:"sms,omitempty"`
	Email    []string `json:"email,omitempty"`
}

type Settlements struct {
	AccountNumber   int64  `json:"account_number,omitempty"`
	IfscCode        string `json:"ifsc_code,omitempty"`
	BeneficiaryName string `json:"beneficiary_name,omitempty"`
}

type Otp struct {
	ContactMobile            string `json:"contact_mobile,omitempty"`
	ExternalReferenceNumber  string `json:"external_reference_number,omitempty"`
	OtpSubmissionTimestamp   string `json:"otp_submission_timestamp,omitempty"`
	OtpVerificationTimestamp string `json:"otp_verification_timestamp,omitempty"`
}
