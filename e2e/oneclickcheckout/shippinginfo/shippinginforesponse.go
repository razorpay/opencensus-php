package shippinginfo

type ShippingInfoResponse struct {
	Address []*Addresses `json:"addresses"`
}
