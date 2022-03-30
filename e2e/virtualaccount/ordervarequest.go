package virtualaccount

type OrderVARequest struct {
	CustomerID string `json:"customer_id"`
	Note Notes `json:"notes,omitempty"`
}

type Notes struct {
	Testing string `json:"testing,omitempty"`
}
