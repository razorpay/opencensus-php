package e2e

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/api/e2e/partnerships/dtos"
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
	"testing"
)

func CreateStakeholder(t *testing.T, accountId string, stakeHolderRequest dtos.StakeholderRequest) dtos.StakeholderResponse {
	var stakeholderResponse dtos.StakeholderResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).POST(e2e.STAKEHOLDER_CREATE_V2, accountId).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(stakeHolderRequest).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &stakeholderResponse)
	return stakeholderResponse
}

func FetchStakeholder(t *testing.T, accountId string, stakeholderId string, stakeHolderRequest dtos.StakeholderRequest) dtos.StakeholderResponse {
	var stakeholderResponse dtos.StakeholderResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).POST(e2e.STAKEHOLDER_FETCH_V2, accountId, stakeholderId).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(stakeHolderRequest).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &stakeholderResponse)
	return stakeholderResponse
}

func UpdateStakeholder(t *testing.T, accountId string, stakeholderId string, stakeHolderRequest dtos.StakeholderRequest) dtos.StakeholderResponse {
	var stakeholderResponse dtos.StakeholderResponse
	obj := httpexpect.New(t, e2e.Config.App.Hostname).PATCH(e2e.STAKEHOLDER_FETCH_V2, accountId, stakeholderId).
		WithBasicAuth(e2e.Config.OnboardingAPIsPartner.Username, e2e.Config.OnboardingAPIsPartner.Password).
		WithJSON(stakeHolderRequest).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &stakeholderResponse)
	return stakeholderResponse
}
