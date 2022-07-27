package payment

type CreateCodPaymentResponse struct {
	RazorpayPaymentID string `json:"razorpay_payment_id,omitempty"`
	RazorpayOrderID   string `json:"razorpay_order_id,omitempty"`
	RazorpaySignature string `json:"razorpay_signature,omitempty"`
}

type CreateNonCodPaymentResponse struct {
	Type      string   `json:"type,,omitempty"`
	Request   *Request `json:"request,omitempty"`
	Version   int      `json:"version,omitempty"`
	PaymentID string   `json:"payment_id,,omitempty"`
	Gateway   string   `json:"gateway,,omitempty"`
	Amount    string   `json:"amount,,omitempty"`
	Image     string   `json:"image,,omitempty"`
	Magic     bool     `json:"magic,,omitempty"`
}

type Request struct {
	URL     string   `json:"url,omitempty"`
	Method  string   `json:"method,omitempty"`
	Content *Content `json:"content,omitempty"`
}

type Content struct {
	Action      string `json:"action,omitempty"`
	Amount      int64  `json:"amount,omitempty"`
	Method      string `json:"method,omitempty"`
	PaymentID   string `json:"payment_id,omitempty"`
	CallbackURL string `json:"callback_url,omitempty"`
	Recurring   int    `json:"recurring,omitempty"`
}
