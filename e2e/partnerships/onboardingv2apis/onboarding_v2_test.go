package e2e

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"os"
	"testing"
)

// This struct corresponds to a test suite.
type OnboardingV2APISuite struct {
	itf.Suite
}

func (s OnboardingV2APISuite) TestAccountsCreation() {
	type positiveTestCases struct {
		description string
		input       AccountsV2Request
	}

	dir, _ := os.Getwd()
	accReqs, _ := GetAccountsV2Request(dir + "/accounts_v2_request_data.json")
	for _, scenario := range []positiveTestCases{
		{
			description: "With Max Payload test",
			input:       accReqs["All_Fields"],
		},
		{
			description: "With Min Payload test",
			input:       accReqs["Only_Mandatory_Fields"],
		},
	} {
		s.Run(scenario.description, func() {
			scenario.input.SetEmail(e2e.GenerateUniqueEmail())
			accRes := CreateAccounts(s.T(), scenario.input)
			assert.Equal(s.T(), scenario.input.Email, accRes.Email)
		})
	}
}

func TestEdge(t *testing.T) {
	suite.Run(t, &OnboardingV2APISuite{Suite: itf.NewSuite(itf.WithTags([]string{TagOnboardingApi}), itf.WithPriority(itf.PriorityP0))})
}
