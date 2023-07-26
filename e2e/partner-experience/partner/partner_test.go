package e2e

import (
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"testing"
)

type PartnerSuite struct {
	itf.Suite
}

func (s PartnerSuite) TestUpdatePartnerType() {
	type testCases struct {
		description string
		input       UpdatePartnerRequest
	}

	for _, scenario := range []testCases{
		{
			description: "Update Partner Type to Reseller",
			input: UpdatePartnerRequest{
				PartnerType: "reseller",
			},
		},
		{
			description: "Update Partner Type to Aggregator",
			input: UpdatePartnerRequest{
				PartnerType: "aggregator",
			},
		},
		{
			description: "Update Partner Type to Fully Managed",
			input: UpdatePartnerRequest{
				PartnerType: "fully_managed",
			},
		},
	} {
		s.Run(scenario.description, func() {
			accRes := UpdatePartnerType(s.T(), scenario.input)
			assert.Equal(s.T(), scenario.input.PartnerType, accRes.PartnerType)
		})
	}
}

func TestPartnerAPIs(t *testing.T) {
	suite.Run(t, &PartnerSuite{Suite: itf.NewSuite(itf.WithTags([]string{"TagPartnerAPI"}), itf.WithPriority(itf.PriorityP0))})
}
