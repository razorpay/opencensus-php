package e2e

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
	"testing"
)

// Create Accounts v2
func CreateAccounts(t *testing.T, accountsV2Request AccountsV2Request) AccountsV2Response {
	var accountsV2Response AccountsV2Response
	obj := httpexpect.New(t, e2e.Config.App.Hostname).POST(e2e.ACCOUNTS_CREATE_V2).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(accountsV2Request).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &accountsV2Response)
	return accountsV2Response
}

// Fetch Accounts v2
func FetchAccounts(t *testing.T, accountId string) AccountsV2Response {
	var accountsV2Response AccountsV2Response
	obj := httpexpect.New(t, e2e.Config.App.Hostname).GET(e2e.ACCOUNTS_FETCH_V2, accountId).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &accountsV2Response)
	return accountsV2Response
}
