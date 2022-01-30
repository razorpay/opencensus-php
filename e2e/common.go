package e2e

import (
	configpkg "github.com/razorpay/api/e2e/config"
)

var Config *Configuration

func init() {
	// Initializes config once for tests to use.
	// See config.go.
	Config = &Configuration{}
	err := configpkg.NewDefaultConfig().Load("default", Config)
	if err != nil {
		panic(err)
	}
}
