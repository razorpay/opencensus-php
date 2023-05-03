package dtos

type StakeholderResponse struct {
	Id                  string               `json:"id"`
	Entity              string               `json:"entity"`
	PercentageOwnership float64              `json:"percentage_ownership"`
	Name                string               `json:"name"`
	Email               string               `json:"email"`
	Relationship        Relationship         `json:"relationship"`
	Phone               Phone                `json:"phone"`
	Addresses           StakeholderAddresses `json:"addresses"`
	Kyc                 Kyc                  `json:"kyc"`
	Notes               interface{}          `json:"notes"`
}
