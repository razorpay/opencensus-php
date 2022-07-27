package coupons

type RemoveCouponRequest struct {
	OrderID          string `json:"order_id"`
	ReferenceID      string `json:"reference_id"`
}
