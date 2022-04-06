package e2e

import (
	"github.com/razorpay/goutils/spine/db"
)

type Configuration struct {
	App                   AppConfig
	PaymentPage           PaymentPageConfig
	SubMerchant           LinkedAccountConfig
	ApiDb                 db.Config
	VirtualAccount        VirtualAccountConfig
	OnboardingAPIsPartner OnboardingAPIsPartnerConfig
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
	LiveUsername   string
	Password       string
	RzpSuperKey    string
	RzpSuperSecret string
}

type VirtualAccountConfig struct {
	// user for creating PP
	User       string
	Role       string
	Username   string
	Password   string
	MerchantId string
}

type OnboardingAPIsPartnerConfig struct {
	// user for hitting Onboarding APIs
	User       string
	Role       string
	Username   string
	Password   string
	MerchantId string
}
