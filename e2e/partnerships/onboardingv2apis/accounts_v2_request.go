package e2e

type AccountsV2 interface {
	SetName(name string)
}

type AccountsV2Request struct {
	Email                      string         `json:"email"`
	Phone                      string         `json:"phone"`
	LegalBusinessName          string         `json:"legal_business_name"`
	CustomerFacingBusinessName string         `json:"customer_facing_business_name,omitempty"`
	BusinessType               string         `json:"business_type"`
	ReferenceId                string         `json:"reference_id"`
	Profile                    *Profile       `json:"profile,omitempty"`
	LegalInfo                  *LegalInfo     `json:"legal_info,omitempty"`
	Brand                      *Brand         `json:"brand,omitempty"`
	Notes                      string         `json:"notes,omitempty"`
	TosAcceptance              *TosAcceptance `json:"tos_acceptance,omitempty"`
	ContactInfo                *ContactInfo   `json:"contact_info,omitempty"`
	Apps                       *Apps          `json:"apps,omitempty"`
}
type Address struct {
	Street1    string `json:"street1,omitempty"`
	Street2    string `json:"street2,omitempty"`
	City       string `json:"city,omitempty"`
	State      string `json:"state,omitempty"`
	PostalCode int    `json:"postal_code,omitempty"`
	Country    string `json:"country,omitempty"`
}
type Addresses struct {
	Registered *Address `json:"registered,omitempty"`
	Operation  *Address `json:"operation,omitempty"`
}
type Profile struct {
	Category      string     `json:"category"`
	Subcategory   string     `json:"subcategory"`
	Description   string     `json:"description,omitempty"`
	Addresses     *Addresses `json:"addresses,omitempty"`
	BusinessModel string     `json:"business_model,omitempty"`
}
type LegalInfo struct {
	Pan string `json:"pan,omitempty"`
	Gst string `json:"gst,omitempty"`
}
type Brand struct {
	Color string `json:"color,omitempty"`
}
type TosAcceptance struct {
	Date      interface{} `json:"date,omitempty"`
	Ip        interface{} `json:"ip,omitempty"`
	UserAgent string      `json:"user_agent,omitempty"`
}
type Contacts struct {
	Email     string `json:"email,omitempty"`
	Phone     string `json:"phone,omitempty"`
	PolicyUrl string `json:"policy_url,omitempty"`
}
type ContactInfo struct {
	Chargeback *Contacts `json:"chargeback,omitempty"`
	Refund     *Contacts `json:"refund,omitempty"`
	Support    *Contacts `json:"support,omitempty"`
}
type App struct {
	URL  string `json:"url,omitempty"`
	Name string `json:"name,omitempty"`
}
type Apps struct {
	Websites []string `json:"websites,omitempty"`
	Android  *[]App   `json:"android,omitempty"`
	Ios      *[]App   `json:"ios,omitempty"`
}

func (p *AccountsV2Request) SetEmail(email string) {
	p.Email = email
}
