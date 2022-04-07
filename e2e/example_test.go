package e2e

import (
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/suite"
	"testing"
)

type ExampleAPITestSuite struct {
	itf.Suite
}

func (s *ExampleAPITestSuite) TestHealth() {
	e := httpexpect.NewWithHeaders(s.T(), Config.App.Hostname, map[string]string{
		DevstackLabelHeader: DevServeHeader,
	})

	e.DoRequestTests(requestTestForHealth)
}

func TestExampleAPI(t *testing.T) {
	suite.Run(t, &ExampleAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{}), itf.WithPriority(itf.PriorityP0))})
}
