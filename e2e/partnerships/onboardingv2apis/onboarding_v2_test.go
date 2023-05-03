package e2e

import (
	"github.com/razorpay/api/e2e"
	dtos2 "github.com/razorpay/api/e2e/common/dtos"
	"github.com/razorpay/api/e2e/common/impl"
	"github.com/razorpay/api/e2e/partnerships/dtos"
	"github.com/razorpay/api/e2e/partnerships/testdata"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"strings"
	"testing"
)

// This struct corresponds to a test suite.
type OnboardingV2APISuite struct {
	itf.Suite
}

func (s OnboardingV2APISuite) TestAccountsCreation() {
	type positiveTestCases struct {
		description string
		input       dtos.AccountsV2Request
	}

	for _, scenario := range []positiveTestCases{
		{
			description: "With Max Payload test",
			input:       testdata.CreateAccountTestCases["All_Fields"].Req,
		},
		{
			description: "With Min Payload test",
			input:       testdata.CreateAccountTestCases["Only_Mandatory_Fields"].Req,
		},
	} {
		s.Run(scenario.description, func() {
			scenario.input.SetEmail(e2e.GenerateUniqueEmail())
			accRes := CreateAccount(s.T(), scenario.input)
			assert.Equal(s.T(), scenario.input.Email, accRes.Email)
		})
	}
}

func (s OnboardingV2APISuite) TestUpdateProductConfigWithSmsNotification() {
	accReqPayload := testdata.CreateAccountTestCases["Only_Mandatory_Fields"].Req
	accReqPayload.SetEmail(e2e.GenerateUniqueEmail())
	accResponse := CreateAccount(s.T(), accReqPayload)

	prdConfigRequest := testdata.CreateProductConfigTestCases["Accept_Product_Tnc"].Req
	prdConfigResponse := CreateProductConfig(s.T(), accResponse.Id, prdConfigRequest)

	prdConfigUpdateRequest := testdata.UpdateProductConfigTestCases["Update_Sms_Notification"].Req
	prdConfigUpdateResponse := UpdateProductConfig(s.T(), accResponse.Id, prdConfigResponse.Id, prdConfigUpdateRequest)
	assert.Equal(s.T(), prdConfigResponse.Id, prdConfigUpdateResponse.Id)
	assert.Equal(s.T(), true, prdConfigUpdateResponse.ActiveConfiguration.Notifications.Sms)
}

func (s OnboardingV2APISuite) TestNoDocOnboardingAccountActivation() {
	accReqPayload := testdata.CreateAccountTestCases["No_Doc_Fields"].Req
	accReqPayload.SetEmail(e2e.GenerateUniqueEmail())
	accResponse := CreateAccount(s.T(), accReqPayload)

	prdConfigRequest := testdata.CreateProductConfigTestCases["Accept_Product_Tnc_No_Doc"].Req
	prdConfigResponse := CreateProductConfig(s.T(), accResponse.Id, prdConfigRequest)
	assert.Equal(s.T(), 10, len(prdConfigResponse.Requirements))

	stakeholderRequest := testdata.StakeholderTestCases["All_Stakeholder_Fields"].Req
	_ = CreateStakeholder(s.T(), accResponse.Id, stakeholderRequest)

	prdConfigUpdateRequest := testdata.UpdateProductConfigTestCases["Settlement_Details"].Req
	prdConfigUpdateResponse := UpdateProductConfig(s.T(), accResponse.Id, prdConfigResponse.Id, prdConfigUpdateRequest)

	prdConfigUpdateRequest = testdata.UpdateProductConfigTestCases["No_Doc_Otp_Details"].Req
	prdConfigUpdateResponse = UpdateProductConfig(s.T(), accResponse.Id, prdConfigUpdateResponse.Id, prdConfigUpdateRequest)

	prdConfigResponse = FetchProductConfig(s.T(), accResponse.Id, prdConfigUpdateResponse.Id)
	assert.Equal(s.T(), 1, len(prdConfigResponse.Requirements))
	assert.Equal(s.T(), UnderReview, prdConfigResponse.ActivationStatus)

	//merchantActivation := FetchMerchantActivationDetails(s.T(), accResponse.Id)
	//assert.Equal(s.T(), prdConfigResponse.ActivationStatus, merchantActivation.ActivationStatus)

	merchantStatusUpdateRequest := dtos2.ActivationStatusRequest{
		ActivationStatus: ActivatedKycPending,
	}

	merchantId := strings.Split(accResponse.Id, "_")[1]
	merchantStatusResponse := impl.UpdateMerchantActivationStatus(s.T(), merchantId, merchantStatusUpdateRequest)
	assert.Equal(s.T(), ActivatedKycPending, merchantStatusResponse.ActivationStatus)
}

func TestOnboardingAPIs(t *testing.T) {
	suite.Run(t, &OnboardingV2APISuite{Suite: itf.NewSuite(itf.WithTags([]string{TagOnboardingApi}), itf.WithPriority(itf.PriorityP0))})
}
