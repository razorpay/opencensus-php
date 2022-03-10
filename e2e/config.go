package e2e

type Configuration struct {
	App         AppConfig
	PaymentPage PaymentPageConfig
	SubMerchant LinkedAccountConfig
	VirtualAccount VirtualAccountConfig

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

type LinkedAccountConfig struct {
	// user for creating Linked Account
	User           string
	Role           string
	Username       string
	Password       string
	RzpSuperKey    string
	RzpSuperSecret string
}

type VirtualAccountConfig struct {
	// user for creating PP
	User     string
	Role     string
	Username string
	Password string
	MerchantId string
}
