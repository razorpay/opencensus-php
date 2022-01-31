package e2e

type Configuration struct {
	App         AppConfig
	PaymentPage PaymentPageConfig
}

type AppConfig struct {
	Hostname string
}

type PaymentPageConfig struct {
	// user for creating PP
	User     string
	Role     string
	Username string
	Password string
}
