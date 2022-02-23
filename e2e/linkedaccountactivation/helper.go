package e2e

import (
	"encoding/json"
	"io/ioutil"
)

func GetLinkedAccountCreateRequest(fixtureName string) (LinkedAccountCreateRequest, error) {
	var r LinkedAccountCreateRequest
	bytes, err := ioutil.ReadFile(fixtureName)
	if err != nil {
		return r, err
	}
	err = json.Unmarshal(bytes, &r)
	return r, err
}

const (
	TagLinkedAccount = "linkedaccount"
)
