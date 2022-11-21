package slit

import (
	"github.com/razorpay/goutils/spine/db"
)

type Configuration struct {
	App                   AppConfig
	ApiDb                 db.Config
}

type AppConfig struct {
	Hostname string
}
