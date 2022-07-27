package shippinginfo

type ShippingInfoRequest struct {
	OrderId string       `json:"order_id"`
	Address []*Addresses `json:"addresses"`
}

type Addresses struct {
	Zipcode     string `json:"zipcode"`
	Country     string `json:"country"`
	City        string `json:"city,omitempty"`
	State       string `json:"state,omitempty"`
	StateCode   string `json:"state_code,omitempty"`
	ShippingFee int64  `json:"shipping_fee,omitempty"`
	Serviceable bool   `json:"serviceable"`
	Cod         bool   `json:"cod,omitempty"`
	CodFee      int64  `json:"cod_fee,omitempty"`
}
