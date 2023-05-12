package dtos

type StakeholderRequest struct {
	Id                  string                 `json:"id,omitempty"`
	PercentageOwnership float64                `json:"percentage_ownership,omitempty"`
	Name                string                 `json:"name,omitempty"`
	Email               string                 `json:"email,omitempty"`
	Relationship        *Relationship          `json:"relationship,omitempty"`
	Phone               *Phone                 `json:"phone,omitempty"`
	Addresses           *StakeholderAddresses  `json:"addresses,omitempty"`
	Kyc                 *Kyc                   `json:"kyc,omitempty"`
	Notes               map[string]interface{} `json:"notes,omitempty"`
}

type Relationship struct {
	Director  bool `json:"director,omitempty"`
	Executive bool `json:"executive,omitempty"`
}

type Phone struct {
	Primary   string `json:"primary,omitempty"`
	Secondary string `json:"secondary,omitempty"`
}

type StakeholderAddresses struct {
	Residential *Residential `json:"residential,omitempty"`
}

type Residential struct {
	Street     string `json:"street,omitempty"`
	City       string `json:"city,omitempty"`
	State      string `json:"state,omitempty"`
	PostalCode string `json:"postal_code,omitempty"`
	Country    string `json:"country,omitempty"`
}

type Kyc struct {
	Pan string `json:"pan,omitempty"`
}
