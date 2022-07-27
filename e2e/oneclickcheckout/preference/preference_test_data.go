package preference

var (
	TestAmount         = int64(50000)
	TestLineItemsTotal = int64(50000)
	TestCurrency       = "INR"
)

type GetPreferenceTest struct {
	Name     string
	OrderId  string
	Response *PreferencesResponse
	Error    error
}

func GetPreferenceResponse() *PreferencesResponse {
	return &PreferencesResponse{
		Order: &Order{
			LineItemsTotal: TestLineItemsTotal,
			Amount:         TestAmount,
			AmountPaid:     0,
			AmountDue:      TestAmount,
			Currency:       TestCurrency,
		},
	}
}
