package e2e

import (
	"fmt"
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/api/e2e/partnerships/dtos"
	"github.com/razorpay/api/e2e/partnerships/testdata"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"testing"
)

type LeadsAPISuite struct {
	itf.Suite
}

func (s LeadsAPISuite) TestAccountsCreation() {
	type testCases struct {
		description string
		input       dtos.AccountsV2Request
	}

	for _, scenario := range []testCases{
		{
			description: "With Max Payload test",
			input:       testdata.CreateAccountTestCases["All_Fields_Leads_API"].Req,
		},
		{
			description: "With Min Payload test",
			input:       testdata.CreateAccountTestCases["Only_Mandatory_Fields_Leads_API"].Req,
		},
	} {
		s.Run(scenario.description, func() {
			scenario.input.SetEmail(e2e.GenerateUniqueEmail())
			accRes := CreateAccount(s.T(), scenario.input)
			fmt.Println(accRes)
			assert.Equal(s.T(), scenario.input.Email, accRes.Email)
		})
	}
}

func (s LeadsAPISuite) TestRequestLOCProduct() {
	accReqPayload := testdata.CreateAccountTestCases["Only_Mandatory_Fields_Leads_API"].Req
	accReqPayload.SetEmail(e2e.GenerateUniqueEmail())
	accResponse := CreateAccount(s.T(), accReqPayload)

	prdConfigRequest := testdata.CreateProductConfigTestCases["Request_LOC_Product"].Req
	prdConfigResponse := CreateProductConfig(s.T(), accResponse.Id, prdConfigRequest)
	fmt.Println(prdConfigResponse)

	prdConfigFetchResponse := FetchProductConfig(s.T(), accResponse.Id, prdConfigResponse.Id)
	assert.Equal(s.T(), prdConfigResponse.Id, prdConfigFetchResponse.Id)
	assert.Equal(s.T(), "line_of_credit", prdConfigFetchResponse.ProductName)
	assert.Empty(s.T(), prdConfigFetchResponse.ActivationStatus)
}

func TestLeadsAPIs(t *testing.T) {
	e2e.Config.OnboardingAPIsPartner.Username = e2e.Config.LeadsAPIsPartner.Username
	e2e.Config.OnboardingAPIsPartner.Password = e2e.Config.LeadsAPIsPartner.Password
	suite.Run(t, &LeadsAPISuite{Suite: itf.NewSuite(itf.WithTags([]string{TagOnboardingApi}), itf.WithTags([]string{TagCapitalLeadsApi}), itf.WithPriority(itf.PriorityP0))})
}
