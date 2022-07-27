package coupons

type ApplyCouponsResponse struct {
	Promotions []*Promotions `json:"promotions,omitempty"`
}

type Promotions struct {
	ReferenceID string `json:"reference_id,omitempty"`
	Type        string `json:"type,omitempty"`
	Code        string `json:"code,omitempty"`
	Value       int64  `json:"value,omitempty"`
	ValueType   string `json:"value_type,omitempty"`
	Description string `json:"description,omitempty"`
}
