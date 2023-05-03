package dtos

type AccountsV2Response struct {
	Id                         string        `json:"id"`
	Email                      string        `json:"email"`
	Type                       string        `json:"type"`
	Status                     string        `json:"status"`
	Profile                    Profile       `json:"profile"`
	Notes                      []byte        `json:"notes"`
	Phone                      string        `json:"phone"`
	ReferenceId                string        `json:"reference_id"`
	BusinessType               string        `json:"business_type"`
	LegalBusinessName          string        `json:"legal_business_name"`
	CustomerFacingBusinessName string        `json:"customer_facing_business_name"`
	LegalInfo                  LegalInfo     `json:"legal_info"`
	Apps                       Apps          `json:"apps"`
	Brand                      Brand         `json:"brand"`
	ContactInfo                ContactInfo   `json:"contact_info"`
	TosAcceptance              TosAcceptance `json:"tos_acceptance"`
}
