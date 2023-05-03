package dtos

type StakeholderRequest struct {
	Id                  string                 `json:"id,omitempty"`
	PercentageOwnership float64                `json:"percentage_ownership,omitempty"`
	Name                string                 `json:"name,omitempty"`
	Email               string                 `json:"email,omitempty"`
	Relationship        Relationship           `json:"relationship,omitempty"`
	Phone               Phone                  `json:"phone,omitempty"`
	Addresses           StakeholderAddresses   `json:"addresses,omitempty"`
	Kyc                 Kyc                    `json:"kyc,omitempty"`
	Notes               map[string]interface{} `json:"notes,omitempty"`
}

type Relationship struct {
	Director  bool `json:"director"`
	Executive bool `json:"executive"`
}

type Phone struct {
	Primary   string `json:"primary"`
	Secondary string `json:"secondary"`
}

type StakeholderAddresses struct {
	Residential Residential `json:"residential"`
}

type Residential struct {
	Street     string `json:"street"`
	City       string `json:"city"`
	State      string `json:"state"`
	PostalCode string `json:"postal_code"`
	Country    string `json:"country"`
}

type Kyc struct {
	Pan string `json:"pan"`
}
