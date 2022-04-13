package e2e

import (
	"github.com/razorpay/dashboard/e2e"
	"math/rand"
	"net/http"
	"strconv"
	"time"
)

func GenerateUniqueEmail() string {
	var email = "e2e"
	var timeStamp = time.Now().UnixNano() / 1000
	email += strconv.FormatInt(timeStamp, 10)

	const letterBytes = "abcdefghijklmnopqrstuvwxyz"
	b := make([]byte, 6)
	rand.Seed(time.Now().UnixNano())
	for i := range b {
		b[i] = letterBytes[rand.Int63()%int64(len(letterBytes))]
	}
	email += string(b)
	email += "@example.com"
	return email
}

func GenerateUniqueMobileNumber() string {
	var contactNumber = "7"
	const letterBytes = "0123456789"
	b := make([]byte, 10)
	rand.Seed(time.Now().UnixNano())
	for i := range b {
		b[i] = letterBytes[rand.Int63()%int64(len(letterBytes))]
	}
	contactNumber += string(b)

	return contactNumber
}

func getToken() (string, string) {
	result := [2]string{}
	resp, err := http.Get(e2e.Config.App.Hostname + "/org")

	if err != nil {
		return "", ""
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == "XSRF-TOKEN" {
			result[0] = cookie.Value
		}

		if cookie.Name == "rzp_usr_session" {
			result[1] = cookie.Value
		}
	}

	return result[0], result[1]
}
