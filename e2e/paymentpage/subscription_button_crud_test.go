package e2e

import (
	"encoding/json"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"os"
	"testing"
)

type SubscriptionButtonAPITestSuite struct {
	itf.Suite
}

func (s *SubscriptionButtonAPITestSuite) TestSubscriptionButtonCreate() {
	var pptest map[string]json.RawMessage
	dir, _ := os.Getwd()
	pptest, _ = GetTestCases(dir + "/../paymentpage/payment_button_create.json")
	var ppReq PaymentPageRequest
	var ppRes PaymentPageResponse
	//To do- Use Go lang table driven approach
	for pos, _ := range pptest {
		json.Unmarshal(pptest[pos], &ppReq)
		ppRes = CreatePaymentPage(s.T(), ppReq)
		verifyCreatedSB(s.T(), ppReq, ppRes)
	}
}
func verifyCreatedSB(t *testing.T, ppRequest PaymentPageRequest, ppResponse PaymentPageResponse) {
	assert.Equal(t, ppRequest.Title, ppResponse.Title)
	assert.NotEmptyf(t, ppResponse.ID, "PP did not created")
	assert.Equal(t, ppRequest.Currency, ppResponse.Currency)
}
func TestSubscriptionButtonAPITestSuite(t *testing.T) {
	suite.Run(t, &SubscriptionButtonAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagSubscriptionButton}), itf.WithPriority(itf.PriorityP0))})
}
