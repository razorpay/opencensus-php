package e2e

import (
	"encoding/json"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"os"
	"testing"
)

type PaymentButtonAPITestSuite struct {
	itf.Suite
}

func (s *PaymentButtonAPITestSuite) TestPaymentButtonCreate() {
	var pptest map[string]json.RawMessage
	dir, _ := os.Getwd()
	pptest, _ = GetTestCases(dir + "/../paymentpage/payment_button_create.json")
	var ppReq PaymentPageRequest
	var ppRes PaymentPageResponse
	//To do- Use Go lang table driven approach
	for pos, _ := range pptest {
		json.Unmarshal(pptest[pos], &ppReq)
		ppRes = CreatePaymentPage(s.T(), ppReq)
		verifyCreatedPB(s.T(), ppReq, ppRes)
	}
}
func verifyCreatedPB(t *testing.T, ppRequest PaymentPageRequest, ppResponse PaymentPageResponse) {
	assert.Equal(t, ppRequest.Title, ppResponse.Title)
	assert.NotEmptyf(t, ppResponse.ID, "PP did not created")
	assert.Equal(t, ppRequest.Currency, ppResponse.Currency)
}
func TestPaymentButtonAPI(t *testing.T) {
	suite.Run(t, &PaymentButtonAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagPaymentButton}), itf.WithPriority(itf.PriorityP0))})
}
