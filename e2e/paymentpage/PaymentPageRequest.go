package e2e

type PaymentPageRequest struct {
	Currency         string      `json:"currency"`
	Title            string      `json:"title"`
	Description      interface{} `json:"description"`
	PaymentPageItems []struct {
		Item struct {
			Name     string `json:"name"`
			Amount   int    `json:"amount"`
			Currency string `json:"currency"`
		} `json:"item"`
	} `json:"payment_page_items"`
}
