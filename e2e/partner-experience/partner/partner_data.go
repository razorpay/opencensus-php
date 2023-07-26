package e2e

type UpdatePartnerRequest struct {
	PartnerType string `json:"partner_type"`
}

type UpdatePartnerResponse struct {
	PartnerType          string `json:"partner_type"`
	HasCommissionConfigs bool   `json:"has_commission_configs"`
}
