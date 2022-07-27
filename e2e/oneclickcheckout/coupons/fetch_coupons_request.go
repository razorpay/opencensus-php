package coupons

type FetchCouponsRequest struct {
	Email                      string         `json:"email"`
    Contact                    string         `json:"contact"`
    OrderID                    string         `json:"order_id"`
	Code                       string         `json:"code,omitempty"`
}
