package shippinginfo

type CodEligibilityRequest struct {
	OrderId string   `json:"order_id"`
	Address *Address `json:"address"`
	Device  *Device  `json:"device"`
}

type Address struct {
	Zipcode  string `json:"zipcode"`
	Country  string `json:"country"`
	City     string `json:"city,omitempty"`
	State    string `json:"state,omitempty"`
	Line1    string `json:"line1,omitempty"`
	Line2    string `json:"line2,omitempty"`
	Tag      string `json:"tag,omitempty"`
	Landmark string `json:"landmark,omitempty"`
	Contact  string `json:"contact,omitempty"`
	Name     string `json:"name,omitempty"`
	Type     string `json:"type,omitempty"`
}

type Device struct {
	Id string `json:"id"`
}
