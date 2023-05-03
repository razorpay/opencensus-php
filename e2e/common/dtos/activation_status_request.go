package dtos

type ActivationStatusRequest struct {
	ActivationStatus string `json:"activation_status"`
}

type ActivationStatusResponse struct {
	Id                          string        `json:"id"`
	Entity                      string        `json:"entity"`
	Name                        string        `json:"name"`
	Email                       string        `json:"email"`
	Activated                   int32         `json:"activated"`
	Locked                      bool          `json:"locked"`
	ActivatedAt                 int32         `json:"activated_at"`
	Live                        bool          `json:"live"`
	HoldFunds                   bool          `json:"hold_funds"`
	PricingPlan                 string        `json:"pricing_plan"`
	ParentId                    interface{}   `json:"parent_id"`
	International               bool          `json:"international"`
	LinkedAccountKyc            bool          `json:"linked_account_kyc"`
	HasKeyAccess                bool          `json:"has_key_access"`
	FeeBearer                   string        `json:"fee_bearer"`
	FeeModel                    string        `json:"fee_model"`
	RefundSource                string        `json:"refund_source"`
	BillingLabel                string        `json:"billing_label"`
	ReceiptEmailEnabled         bool          `json:"receipt_email_enabled"`
	InvoiceLabelField           string        `json:"invoice_label_field"`
	Channel                     string        `json:"channel"`
	ConvertConcurrency          interface{}   `json:"convert_concurrency"`
	MaxPaymentAmount            int32         `json:"max_payment_amount"`
	AutoRefundDelay             int32         `json:"auto_refund_delay"`
	AutoCaptureLateAuth         bool          `json:"auto_capture_late_auth"`
	BrandColor                  interface{}   `json:"brand_color"`
	Handle                      interface{}   `json:"handle"`
	RiskRating                  int32         `json:"risk_rating"`
	RiskThreshold               int32         `json:"risk_threshold"`
	PartnerType                 string        `json:"partner_type"`
	CreatedAt                   int32         `json:"created_at"`
	UpdatedAt                   int32         `json:"updated_at"`
	ArchivedAt                  int32         `json:"archived_at"`
	SuspendedAt                 int32         `json:"suspended_at"`
	LogoUrl                     interface{}   `json:"logo_url"`
	OrgId                       string        `json:"org_id"`
	Groups                      []interface{} `json:"groups"`
	Admins                      []interface{} `json:"admins"`
	Notes                       []interface{} `json:"notes"`
	WhitelistedIpsLive          []interface{} `json:"whitelisted_ips_live"`
	WhitelistedIpsTest          []interface{} `json:"whitelisted_ips_test"`
	FeeCreditsThreshold         interface{}   `json:"fee_credits_threshold"`
	Submit                      int32         `json:"submit"`
	Submitted                   bool          `json:"submitted"`
	ActivationFlow              string        `json:"activation_flow"`
	InternationalActivationFlow string        `json:"international_activation_flow"`
	ActivationStatus            string        `json:"activation_status"`
	ActivationProgress          int32         `json:"activation_progress"`
	BusinessName                string        `json:"business_name"`
	ContactName                 string        `json:"contact_name"`
	ContactMobile               string        `json:"contact_mobile"`
	CompanyCin                  string        `json:"company_cin"`
	CompanyPan                  string        `json:"company_pan"`
	PromoterPanName             string        `json:"promoter_pan_name"`
	BusinessProofUrl            string        `json:"business_proof_url"`
	AddressProofUrl             string        `json:"address_proof_url"`
	PromoterAddressUrl          string        `json:"promoter_address_url"`
}
