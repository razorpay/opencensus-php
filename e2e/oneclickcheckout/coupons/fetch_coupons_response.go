package coupons

type FetchCouponsResponse struct {
	Promotions []*FetchPromotions `json:"promotions,omitempty"`
}

type FetchPromotions struct {
	Code        string   `json:"code,omitempty"`
	Summary     string   `json:"summary,omitempty"`
	Description string   `json:"description,omitempty"`
	Tnc         []string `json:"tnc,omitempty"`
}
