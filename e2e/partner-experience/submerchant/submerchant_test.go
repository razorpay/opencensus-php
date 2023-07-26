package e2e

import (
	"github.com/razorpay/api/e2e"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"testing"
)

type SubMerchantCreateSuite struct {
	itf.Suite
}

func (s SubMerchantCreateSuite) TestSubMerchantCreation() {
	type testCases struct {
		description string
		input       SubMerchantCreateRequest
	}

	for _, scenario := range []testCases{
		{
			description: "Create SubMerchant creation With Min Payload test",
			input: SubMerchantCreateRequest{
				Name:    "Testing SubMerchant",
				Product: "primary",
			},
		},
	} {
		s.Run(scenario.description, func() {
			scenario.input.SetEmail(e2e.GenerateUniqueEmail())
			response := CreateSubMerchant(s.T(), scenario.input)
			assert.Equal(s.T(), scenario.input.Email, response.Email)
			assert.Equal(s.T(), scenario.input.Name, response.Name)
		})
	}
}

func (s SubMerchantCreateSuite) TestGetSubMerchant() {
	subMerchantReq := SubMerchantCreateRequest{
		Name:    "Testing SubMerchant",
		Product: "primary",
	}
	subMerchantReq.SetEmail(e2e.GenerateUniqueEmail())
	subMerchantRes := CreateSubMerchant(s.T(), subMerchantReq)

	getSubMerchantResponse := GetSubMerchant(s.T(), subMerchantRes.Id)
	assert.Equal(s.T(), subMerchantRes.Id, getSubMerchantResponse.Id)
	assert.Equal(s.T(), subMerchantReq.Email, getSubMerchantResponse.Email)
	assert.Equal(s.T(), subMerchantReq.Name, subMerchantReq.Name)
}

func TestSubMerchantAPIs(t *testing.T) {
	suite.Run(t, &SubMerchantCreateSuite{Suite: itf.NewSuite(itf.WithTags([]string{"TagSubMerchant"}), itf.WithPriority(itf.PriorityP0))})
}
