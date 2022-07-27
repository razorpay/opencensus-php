package loggedoutflow

import (
	"testing"

	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/suite"
)

type LoggedOutFlowTestSuite struct {
	itf.Suite
}

func TestLoggedoutFlow(t *testing.T) {
	suite.Run(t, &LoggedOutFlowTestSuite{Suite: itf.NewSuite(itf.WithTags([]string{"loggedoutflow"}), itf.WithPriority(itf.PriorityP0))})
}
