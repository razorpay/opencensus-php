package e2e

type PaymentPageResponse struct {
	ID              string      `json:"id"`
	Amount          interface{} `json:"amount"`
	Currency        string      `json:"currency"`
	CurrencySymbol  string      `json:"currency_symbol"`
	ExpireBy        interface{} `json:"expire_by"`
	TimesPayable    interface{} `json:"times_payable"`
	TimesPaid       int         `json:"times_paid"`
	TotalAmountPaid int         `json:"total_amount_paid"`
	Status          string      `json:"status"`
	StatusReason    interface{} `json:"status_reason"`
	ShortURL        string      `json:"short_url"`
	UserID          string      `json:"user_id"`
	User            struct {
		ID                          string `json:"id"`
		Name                        string `json:"name"`
		Email                       string `json:"email"`
		ContactMobile               string `json:"contact_mobile"`
		ContactMobileVerified       bool   `json:"contact_mobile_verified"`
		EmailVerified               bool   `json:"email_verified"`
		SecondFactorAuth            bool   `json:"second_factor_auth"`
		SecondFactorAuthEnforced    bool   `json:"second_factor_auth_enforced"`
		SecondFactorAuthSetup       bool   `json:"second_factor_auth_setup"`
		OrgEnforcedSecondFactorAuth bool   `json:"org_enforced_second_factor_auth"`
		Restricted                  bool   `json:"restricted"`
		Confirmed                   bool   `json:"confirmed"`
		AccountLocked               bool   `json:"account_locked"`
		CreatedAt                   int    `json:"created_at"`
		SignupViaEmail              int    `json:"signup_via_email"`
	} `json:"user"`
	Title            string        `json:"title"`
	Description      string        `json:"description"`
	Notes            []interface{} `json:"notes"`
	SupportContact   string        `json:"support_contact"`
	SupportEmail     string        `json:"support_email"`
	Terms            string        `json:"terms"`
	Type             string        `json:"type"`
	PaymentPageItems []struct {
		ID            string `json:"id"`
		Entity        string `json:"entity"`
		PaymentLinkID string `json:"payment_link_id"`
		Item          struct {
			ID           string      `json:"id"`
			Active       bool        `json:"active"`
			Name         string      `json:"name"`
			Description  string      `json:"description"`
			Amount       int         `json:"amount"`
			UnitAmount   int         `json:"unit_amount"`
			Currency     string      `json:"currency"`
			Type         string      `json:"type"`
			Unit         interface{} `json:"unit"`
			TaxInclusive bool        `json:"tax_inclusive"`
			HsnCode      interface{} `json:"hsn_code"`
			SacCode      interface{} `json:"sac_code"`
			TaxRate      interface{} `json:"tax_rate"`
			TaxID        interface{} `json:"tax_id"`
			TaxGroupID   interface{} `json:"tax_group_id"`
			CreatedAt    int         `json:"created_at"`
		} `json:"item"`
		Mandatory       bool        `json:"mandatory"`
		ImageURL        string      `json:"image_url"`
		Stock           int         `json:"stock"`
		QuantitySold    int         `json:"quantity_sold"`
		TotalAmountPaid int         `json:"total_amount_paid"`
		MinPurchase     int         `json:"min_purchase"`
		MaxPurchase     int         `json:"max_purchase"`
		MinAmount       interface{} `json:"min_amount"`
		MaxAmount       interface{} `json:"max_amount"`
		PlanID          interface{} `json:"plan_id"`
		ProductConfig   interface{} `json:"product_config"`
	} `json:"payment_page_items"`
	CreatedAt int `json:"created_at"`
	UpdatedAt int `json:"updated_at"`
}
