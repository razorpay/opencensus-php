package order

type Orders interface {
	SetName(name string)
}

type OrdersRequest struct {
	Amount         int64       `json:"amount"`
	Currency       string      `json:"currency"`
	Receipt        string      `json:"receipt"`
	LineItemsTotal int64       `json:"line_items_total,omitempty"`
	PaymentCapture int         `json:"payment_capture"`
	Note           *Notes      `json:"notes,omitempty"`
	LineItem       []*LineItem `json:"line_items,omitempty"`
}

type Notes struct {
	NotesKey string `json:"notes_key_1,omitempty"`
}

type LineItem struct {
	Name        string `json:"name"`
	Description string `json:"description"`
	Price       int64  `json:"price"`
	Quantity    int64  `json:"quantity"`
}

type UpdateCustomerDetailRequest struct {
	CustomerDetail *CustomerDetail `json:"customer_details,omitempty"`
}

type CustomerDetail struct {
	Contact         string   `json:"contact"`
	Email           string   `json:"email"`
	ShippingAddress *Address `json:"shipping_address,omitempty"`
	BillingAddress  *Address `json:"billing_address,omitempty"`
}
type Address struct {
	Name    string `json:"name"`
	Type    string `json:"type"`
	Line1   string `json:"line1"`
	Line2   string `json:"line2"`
	Zipcode string `json:"zipcode"`
	City    string `json:"city"`
	State   string `json:"state"`
	Country string `json:"country"`
}
