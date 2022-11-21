package caw

import (
	"github.com/razorpay/api/slit"
	"github.com/razorpay/goutils/itf"
	"github.com/razorpay/goutils/itf/httpexpect"
	"github.com/stretchr/testify/suite"
	"net/http"
	"testing"
)

type HealthSuite struct {
	itf.Suite
}

func TestHealthSuite(t *testing.T) {
	suite.Run(t, &HealthSuite{itf.NewSuite(itf.WithPriority(itf.PriorityP0))})
}

func (s *HealthSuite) SetupSuite() {
	s.Suite.SetupSuite()
}

func (s *HealthSuite) TearDownSuite() {

}

func (s *HealthSuite) TestStatus() {
	httpexpect.New(s.T(), slit.Config.App.Hostname).
		GET("/").
		Expect().
		Status(http.StatusOK)
}
