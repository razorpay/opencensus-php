package virtualaccount

import (
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"testing"
)
type VirtualAccountCheckoutTestSuite struct {
	itf.Suite
}

func (s *VirtualAccountCheckoutTestSuite) TestCreateAndUpdateVAForAnOrder() {
	orderReq:= OrderRequest{
		Amount:  "1000",
		Currency: "INR",
		PaymentCapture: "1",
		Receipt: "1647946026",
		AppOffer: false,
		Discount: false,
		ForceOffer: false,
		PartialPayment: false,
	}
	orderRes:= CreateOrder(s.T(), orderReq)
	orderVAReq := OrderVARequest{
		CustomerID: "cust_Iwf3ydmuCV3y8R",
	}
	orderVARes := CreateVAForOrder(s.T(), orderVAReq,orderRes)
	orderReqSec:= OrderRequest{
		Amount:  "2000",
		Currency: "INR",
		PaymentCapture: "1",
		Receipt: "1647946026",
		AppOffer: false,
		Discount: false,
		ForceOffer: false,
		PartialPayment: false,
	}
	orderResSec:= CreateOrder(s.T(), orderReqSec)
	orderVAReqSec := OrderVARequest{
		CustomerID: "cust_Iwf3ydmuCV3y8R",
	}
	orderVAResSec := CreateVAForOrder(s.T(), orderVAReqSec,orderResSec)
	assert.Equal(s.T(), orderVARes.ID,orderVAResSec.ID, "Different VA account got created")
}

func (s *VirtualAccountCheckoutTestSuite) TestCreateAndUpdateVAForAnOrderPartialPayment() {
	orderReq:= OrderRequest{
		Amount:  "1000",
		Currency: "INR",
		PaymentCapture: "1",
		Receipt: "1647946026",
		AppOffer: false,
		Discount: false,
		ForceOffer: false,
		PartialPayment: true,
	}
	orderRes:= CreateOrder(s.T(), orderReq)
	orderVAReq := OrderVARequest{
		CustomerID: "cust_Iwf3ydmuCV3y8R",
	}
	orderVARes := CreateVAForOrder(s.T(), orderVAReq,orderRes)
	orderReqSec:= OrderRequest{
		Amount:  "2000",
		Currency: "INR",
		PaymentCapture: "1",
		Receipt: "1647946026",
		AppOffer: false,
		Discount: false,
		ForceOffer: false,
		PartialPayment: true,
	}
	orderResSec:= CreateOrder(s.T(), orderReqSec)
	orderVAReqSec := OrderVARequest{
		CustomerID: "cust_Iwf3ydmuCV3y8R",
	}
	orderVAResSec := CreateVAForOrder(s.T(), orderVAReqSec,orderResSec)
	assert.Equal(s.T(), orderVARes.ID,orderVAResSec.ID, "Different VA account got created")
}

func (s *VirtualAccountCheckoutTestSuite) TestCreateAndUpdateVAForInvalidCustomerID() {
	orderReq:= OrderRequest{
		Amount:  "1000",
		Currency: "INR",
		PaymentCapture: "1",
		Receipt: "1647946026",
		AppOffer: false,
		Discount: false,
		ForceOffer: false,
		PartialPayment: false,
	}
	orderRes:= CreateOrder(s.T(), orderReq)
	orderVAReq := OrderVARequest{
		CustomerID: "cust_Iwf3ydmuCV3y8",
	}
	err := CreateVAForOrderNegative(s.T(), orderVAReq,orderRes)
	assert.NotNil(s.T(), err.Error.Description)
}

func (s *VirtualAccountCheckoutTestSuite) TestCreateAndUpdateVAWithNotes() {
	orderReq:= OrderRequest{
		Amount:  "1000",
		Currency: "INR",
		PaymentCapture: "1",
		Receipt: "1647946026",
		AppOffer: false,
		Discount: false,
		ForceOffer: false,
		PartialPayment: false,
	}
	orderRes:= CreateOrder(s.T(), orderReq)
	orderVAReq := OrderVARequest{
		CustomerID: "cust_Iwf3ydmuCV3y8R",
		Note: Notes{
			Testing: "va_checkout_notes",
		},
	}
	orderVARes := CreateVAForOrder(s.T(), orderVAReq,orderRes)
	orderReqSec:= OrderRequest{
		Amount:  "2000",
		Currency: "INR",
		PaymentCapture: "1",
		Receipt: "1647946026",
		AppOffer: false,
		Discount: false,
		ForceOffer: false,
		PartialPayment: false,
	}
	orderResSec:= CreateOrder(s.T(), orderReqSec)
	orderVAReqSec := OrderVARequest{
		CustomerID: "cust_Iwf3ydmuCV3y8R",
		Note: Notes{
			Testing: "va_checkout_notes",
		},
	}
	orderVAResSec := CreateVAForOrder(s.T(), orderVAReqSec,orderResSec)
	assert.Equal(s.T(), orderVARes.ID,orderVAResSec.ID, "Different VA account got created")
}

func TestVirtualAccountCheckout(t *testing.T) {
	suite.Run(t, &VirtualAccountCheckoutTestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagVirtualAccount}), itf.WithPriority(itf.PriorityP0))})
}

