package slit

import (
	"fmt"

	"github.com/razorpay/goutils/spine/db"
)

var (
	// DB holds the application db connection.

	ApiDB *db.DB
	BvsDB *db.DB
)

func InitApiDb() error {
	var err error

	// Init Db
	fmt.Println("Initializing Db..")
	ApiDB, err = db.NewDb(&Config.ApiDb)
	if err != nil {
		return err
	}
	fmt.Println("Initializing DB successful")
	return nil
}
