package e2e

type Configuration struct {
	App PPConfig
	// Payment Page Config
	PaymentPage PPConfig
}
type PPConfig struct {
	Hostname string
	// user for creating PP
	User     string
	Role     string
	Username string
	Password string
}


