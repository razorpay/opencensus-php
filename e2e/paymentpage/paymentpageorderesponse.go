package e2e
type PaymentPageOrderResponse struct {
	Order struct {
		ID         string        `json:"id"`
		Entity     string        `json:"entity"`
		Amount     int           `json:"amount"`
		AmountPaid int           `json:"amount_paid"`
		AmountDue  int           `json:"amount_due"`
		Currency   string        `json:"currency"`
		Receipt    interface{}   `json:"receipt"`
		Offers     []string      `json:"offers"`
		Status     string        `json:"status"`
		Attempts   int           `json:"attempts"`
		Notes      []interface{} `json:"notes"`
		CreatedAt  int           `json:"created_at"`
	} `json:"order"`
	LineItems []struct {
		ID            string      `json:"id"`
		ItemID        string      `json:"item_id"`
		RefID         string      `json:"ref_id"`
		RefType       string      `json:"ref_type"`
		Name          string      `json:"name"`
		Description   string      `json:"description"`
		Amount        int         `json:"amount"`
		UnitAmount    int         `json:"unit_amount"`
		GrossAmount   int         `json:"gross_amount"`
		TaxAmount     int         `json:"tax_amount"`
		TaxableAmount int         `json:"taxable_amount"`
		NetAmount     int         `json:"net_amount"`
		Currency      string      `json:"currency"`
		Type          string      `json:"type"`
		TaxInclusive  bool        `json:"tax_inclusive"`
		HsnCode       interface{} `json:"hsn_code"`
		SacCode       interface{} `json:"sac_code"`
		TaxRate       interface{} `json:"tax_rate"`
		Unit          interface{} `json:"unit"`
		Quantity      int         `json:"quantity"`
	} `json:"line_items"`
}
