package e2e

import (
	"encoding/json"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"os"
	"testing"
)
type PaymentPageAPITestSuite struct {
	itf.Suite
}
func (s *PaymentPageAPITestSuite) TestPaymentPageCreate() {
	var pptest map[string]json.RawMessage
	dir, _ := os.Getwd()
	pptest, _ = GetTestCases(dir+"/../paymentpage/ppcreate.json")
	var ppReq PaymentPageRequest
	var ppRes PaymentPageResponse
	//To do- Use Go lang table driven approach
	for pos, _ := range pptest  {
		json.Unmarshal(pptest[pos], &ppReq)
		ppRes=CreatePaymentPage(s.T(),ppReq)
		verifyCreatedPP(s.T(),ppReq,ppRes)
	}
}
func verifyCreatedPP(t *testing.T,ppRequest PaymentPageRequest,ppResponse PaymentPageResponse){
	assert.Equal(t,ppRequest.Title,ppResponse.Title)
	assert.NotEmptyf(t, ppResponse.ID,"PP did not created")
	assert.Equal(t, ppRequest.Description,ppResponse.Description)
	assert.Equal(t, ppRequest.Currency,ppResponse.Currency)
}
func TestPaymentPageAPI(t *testing.T) {
	suite.Run(t, &PaymentPageAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagPaymentPage}), itf.WithPriority(itf.PriorityP0))})
}

