package e2e

type SubMerchantCreateRequest struct {
	Name    string `json:"name"`
	Email   string `json:"email"`
	Product string `json:"product"`
}

type SubMerchantCreateResponse struct {
	Id            string `json:"id"`
	Entity        string `json:"entity"`
	Name          string `json:"name"`
	Email         string `json:"email"`
	Activated     bool   `json:"activated"`
	ActivatedAt   int    `json:"activated_at"`
	Live          bool   `json:"live"`
	HoldFunds     bool   `json:"hold_funds"`
	PricingPlanId string `json:"pricing_plan_id"`
	ParentId      string `json:"parent_id"`
}

func (s *SubMerchantCreateRequest) SetEmail(email string) {
	s.Email = email
}

func (s *SubMerchantCreateRequest) SetName(name string) {
	s.Name = name
}

func (s *SubMerchantCreateRequest) SetProduct(product string) {
	s.Product = product
}
