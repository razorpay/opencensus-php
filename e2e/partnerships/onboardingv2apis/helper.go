package e2e

import (
	"encoding/json"
	"io/ioutil"
)

func GetAccountsV2Request(fixtureName string) (map[string]AccountsV2Request, error) {
	var r map[string]AccountsV2Request
	bytes, err := ioutil.ReadFile(fixtureName)
	if err != nil {
		return r, err
	}
	err = json.Unmarshal(bytes, &r)
	return r, err
}

const (
	TagOnboardingApi = "onboardingapi"
)
