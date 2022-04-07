package e2e

import (
	configpkg "github.com/razorpay/dashboard/e2e/config"
	"os"
)

var Config *Configuration

const DevstackLabelHeader = "rzpctx-dev-serve-user"

var DevServeHeader string

func init() {
	// Initializes config once for tests to use.
	// See config.go.

	Config = &Configuration{}
	err := configpkg.NewDefaultConfig().Load("default", Config)
	if err != nil {
		panic(err)
	}

	DevServeHeader = os.Getenv("DEVSTACK_LABEL")

	if DevServeHeader == "" {
		panic("DEVSTACK_LABEL is not set")
	}
}
