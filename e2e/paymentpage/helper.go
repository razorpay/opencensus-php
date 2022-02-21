package e2e

import (
	"encoding/json"
	"io/ioutil"
)

func GetTestCases(fixtureName string) (map[string]json.RawMessage, error) {
	var r map[string]json.RawMessage
	bytes, err := ioutil.ReadFile(fixtureName)
	if err != nil {
		return r, err
	}
	err = json.Unmarshal(bytes, &r)
	return r, err
}

const (
	TagPaymentPage        = "paymentpage"
	TagPaymentButton      = "paymentbutton"
	TagSubscriptionButton = "subscriptionbutton"
)
