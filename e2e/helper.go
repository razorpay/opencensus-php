package e2e

import (
	"math/rand"
	"strconv"
	"time"
)

func GenerateUniqueEmail() string {
	var email = "e2e"
	var timeStamp = time.Now().UnixNano() / 1000
	email += strconv.FormatInt(timeStamp, 10)

	const letterBytes = "abcdefghijklmnopqrstuvwxyz"
	b := make([]byte, 6)
	for i := range b {
		b[i] = letterBytes[rand.Int63()%int64(len(letterBytes))]
	}
	email += string(b)
	email += "@example.com"
	return email
}
