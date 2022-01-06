package e2e

import (
	configpkg "github.com/razorpay/api/e2e/config"
)

var config *Config

func init() {
	// Initializes config once for tests to use.
	// See config.go.
	config = &Config{}
	err := configpkg.NewDefaultConfig().Load("default", config)
	if err != nil {
		panic(err)
	}
}
