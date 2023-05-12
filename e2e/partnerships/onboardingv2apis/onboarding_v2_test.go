package e2e

import (
	"fmt"
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
	"time"
)

// This struct corresponds to a test suite.
type OnboardingV2APISuite struct {
	itf.Suite
}

func (s OnboardingV2APISuite) TestAccountsCreation() {
	type testCases struct {
		description string
		input       dtos.AccountsV2Request
	}

	for _, scenario := range []testCases{
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

func (s OnboardingV2APISuite) TestAccountActivationStatusSuccessForNoDocMerchant() {
	type testCases struct {
		description              string
		businessType             string
		noOfRequirements         int
		noOfOptionalRequirements int
	}
	for _, scenario := range []testCases{
		{
			description:              "For not_yet_registered business type",
			businessType:             "not_yet_registered",
			noOfRequirements:         9,
			noOfOptionalRequirements: 1,
		},
		{
			description:              "For proprietorship business type",
			businessType:             "proprietorship",
			noOfRequirements:         9,
			noOfOptionalRequirements: 3,
		},
		{
			description:              "For ngo business type",
			businessType:             "ngo",
			noOfRequirements:         9,
			noOfOptionalRequirements: 5,
		},
	} {
		s.Run(scenario.description, func() {
			onboardingDetails := s.provideAllRequirementsForNoDoc(scenario.businessType, scenario.noOfRequirements, scenario.noOfOptionalRequirements)
			s.activateNoDocMerchant(onboardingDetails.AccountId)
		})
	}
}

func (s OnboardingV2APISuite) TestAccountActivationWithPanVerificationRetryForNoDocMerchant() {
	type positiveTestCases struct {
		description              string
		businessType             string
		noOfRequirements         int
		noOfOptionalRequirements int
	}
	for _, scenario := range []positiveTestCases{
		{
			description:              "For not_yet_registered business type",
			businessType:             "not_yet_registered",
			noOfRequirements:         9,
			noOfOptionalRequirements: 1,
		},
		{
			description:              "For proprietorship business type",
			businessType:             "proprietorship",
			noOfRequirements:         9,
			noOfOptionalRequirements: 3,
		},
		{
			description:              "For ngo business type",
			businessType:             "ngo",
			noOfRequirements:         9,
			noOfOptionalRequirements: 5,
		},
	} {
		s.Run(scenario.description, func() {
			onboardingDetails := s.provideAllRequirementsForNoDoc(scenario.businessType, scenario.noOfRequirements, scenario.noOfOptionalRequirements)
			fmt.Printf("%+v", onboardingDetails)

			time.Sleep(time.Duration(1) * time.Minute)

			merchantId := strings.Split(onboardingDetails.AccountId, "_")[1]
			validationId := GetBvsValidationIdWithOwnerIdForArtefact(merchantId, "personal_pan")
			fmt.Println("Validation Id " + validationId)

			impl.SendMockBvsValidationEvent(s.T(), GetBvsValidationData(validationId, "failed"))

			// devstack is taking longer to complete background job
			time.Sleep(time.Duration(1) * time.Minute)

			if scenario.businessType == "ngo" {
				validationId := GetBvsValidationIdWithOwnerIdForArtefact(merchantId, "business_pan")
				fmt.Println("Validation Id " + validationId)

				impl.SendMockBvsValidationEvent(s.T(), GetBvsValidationData(validationId, "failed"))

				time.Sleep(time.Duration(1) * time.Minute)
			}

			prdConfigResponse := FetchProductConfig(s.T(), onboardingDetails.AccountId, onboardingDetails.ProductConfigId)
			assert.Equal(s.T(), scenario.noOfOptionalRequirements, len(s.fetchRequirementsOfStatus(prdConfigResponse.Requirements, "optional")))
			assert.Equal(s.T(), 1, len(s.fetchRequirementsOfStatus(prdConfigResponse.Requirements, "required")))
			assert.Equal(s.T(), NeedsClarification, prdConfigResponse.ActivationStatus)

			if scenario.businessType == "ngo" {
				accUpdateReqPayload := testdata.UpdateAccountTestCases["Update_Pan2"].Req
				_ = UpdateAccount(s.T(), onboardingDetails.AccountId, accUpdateReqPayload)
			} else {
				stakeholderRequest := testdata.StakeholderTestCases["Update_Pan"].Req
				_ = UpdateStakeholder(s.T(), onboardingDetails.AccountId, onboardingDetails.StakeholderId, stakeholderRequest)
			}

			prdConfigResponse = FetchProductConfig(s.T(), onboardingDetails.AccountId, onboardingDetails.ProductConfigId)
			assert.Equal(s.T(), UnderReview, prdConfigResponse.ActivationStatus)

			s.activateNoDocMerchant(onboardingDetails.AccountId)
		})
	}
}

func (s OnboardingV2APISuite) TestAccountActivationStatusWithPanVerificationRetryExhaustedForNoDocMerchant() {
	s.T().Skip("Test is intermittently failing because of 502,404 errors")
	type positiveTestCases struct {
		description              string
		businessType             string
		noOfRequirements         int
		noOfOptionalRequirements int
	}
	for _, scenario := range []positiveTestCases{
		{
			description:              "For not_yet_registered business type",
			businessType:             "not_yet_registered",
			noOfRequirements:         9,
			noOfOptionalRequirements: 1,
		},
		{
			description:              "For proprietorship business type",
			businessType:             "proprietorship",
			noOfRequirements:         9,
			noOfOptionalRequirements: 3,
		},
		{
			description:              "For ngo business type",
			businessType:             "ngo",
			noOfRequirements:         9,
			noOfOptionalRequirements: 5,
		},
	} {
		s.Run(scenario.description, func() {
			onboardingDetails := s.provideAllRequirementsForNoDoc(scenario.businessType, scenario.noOfRequirements, scenario.noOfOptionalRequirements)
			fmt.Printf("%+v", onboardingDetails)

			merchantId := strings.Split(onboardingDetails.AccountId, "_")[1]
			validationId := GetBvsValidationIdWithOwnerIdForArtefact(merchantId, "personal_pan")
			fmt.Println("Validation Id " + validationId)

			impl.SendMockBvsValidationEvent(s.T(), GetBvsValidationData(validationId, "failed"))

			time.Sleep(time.Duration(1) * time.Minute)

			if scenario.businessType == "ngo" {
				validationId := GetBvsValidationIdWithOwnerIdForArtefact(merchantId, "business_pan")
				fmt.Println("Validation Id " + validationId)

				impl.SendMockBvsValidationEvent(s.T(), GetBvsValidationData(validationId, "failed"))

				time.Sleep(time.Duration(1) * time.Minute)
			}

			prdConfigResponse := FetchProductConfig(s.T(), onboardingDetails.AccountId, onboardingDetails.ProductConfigId)
			assert.Equal(s.T(), scenario.noOfOptionalRequirements, len(s.fetchRequirementsOfStatus(prdConfigResponse.Requirements, "optional")))
			assert.Equal(s.T(), 1, len(s.fetchRequirementsOfStatus(prdConfigResponse.Requirements, "required")))
			assert.Equal(s.T(), NeedsClarification, prdConfigResponse.ActivationStatus)

			if scenario.businessType == "ngo" {
				accUpdateReqPayload := testdata.UpdateAccountTestCases["Update_Pan2"].Req
				_ = UpdateAccount(s.T(), onboardingDetails.AccountId, accUpdateReqPayload)
			} else {
				stakeholderRequest := testdata.StakeholderTestCases["Update_Pan"].Req
				_ = UpdateStakeholder(s.T(), onboardingDetails.AccountId, onboardingDetails.StakeholderId, stakeholderRequest)
			}

			time.Sleep(time.Duration(2) * time.Minute)

			validationId = GetBvsValidationIdWithOwnerIdForArtefact(merchantId, "personal_pan")
			fmt.Println("Validation Id " + validationId)

			impl.SendMockBvsValidationEvent(s.T(), GetBvsValidationData(validationId, "failed"))

			time.Sleep(time.Duration(1) * time.Minute)

			if scenario.businessType == "ngo" {
				validationId = GetBvsValidationIdWithOwnerIdForArtefact(merchantId, "business_pan")
				fmt.Println("Validation Id " + validationId)

				impl.SendMockBvsValidationEvent(s.T(), GetBvsValidationData(validationId, "failed"))

				time.Sleep(time.Duration(1) * time.Minute)
			}

			prdConfigResponse = FetchProductConfig(s.T(), onboardingDetails.AccountId, onboardingDetails.ProductConfigId)
			assert.Equal(s.T(), NeedsClarification, prdConfigResponse.ActivationStatus)
		})
	}
}

func (s OnboardingV2APISuite) TestAccountActivationWithBankVerificationFailureForNoDocMerchant() {
	type positiveTestCases struct {
		description              string
		businessType             string
		noOfRequirements         int
		noOfOptionalRequirements int
	}
	for _, scenario := range []positiveTestCases{
		{
			description:              "For not_yet_registered business type",
			businessType:             "not_yet_registered",
			noOfRequirements:         9,
			noOfOptionalRequirements: 1,
		},
		{
			description:              "For proprietorship business type",
			businessType:             "proprietorship",
			noOfRequirements:         9,
			noOfOptionalRequirements: 3,
		},
		{
			description:              "For ngo business type",
			businessType:             "ngo",
			noOfRequirements:         9,
			noOfOptionalRequirements: 5,
		},
	} {
		s.Run(scenario.description, func() {
			onboardingDetails := s.provideAllRequirementsForNoDoc(scenario.businessType, scenario.noOfRequirements, scenario.noOfOptionalRequirements)

			merchantId := strings.Split(onboardingDetails.AccountId, "_")[1]
			validationId := GetBvsValidationIdWithOwnerIdForArtefact(merchantId, "bank_account")
			fmt.Println("Validation Id " + validationId)
			impl.SendMockBvsValidationEvent(s.T(), GetBvsValidationData(validationId, "failed"))

			prdConfigResponse := FetchProductConfig(s.T(), onboardingDetails.AccountId, onboardingDetails.ProductConfigId)
			assert.Equal(s.T(), scenario.noOfOptionalRequirements, len(s.fetchRequirementsOfStatus(prdConfigResponse.Requirements, "optional")))
			assert.Equal(s.T(), 0, len(s.fetchRequirementsOfStatus(prdConfigResponse.Requirements, "required")))
			assert.Equal(s.T(), UnderReview, prdConfigResponse.ActivationStatus)

			s.activateNoDocMerchant(onboardingDetails.AccountId)
		})
	}
}

func (s OnboardingV2APISuite) provideAllRequirementsForNoDoc(businessType string, expectedRequirements int, expectedOptionalRequirements int) dtos.OnboardingDetails {
	accReqPayload := testdata.CreateAccountTestCases["No_Doc_Fields"].Req
	accReqPayload.SetEmail(e2e.GenerateUniqueEmail())
	accReqPayload.SetBusinessType(businessType)

	if businessType == "ngo" {
		accReqPayload.SetLegalInfo(testdata.UpdateAccountTestCases["Update_Pan"].Req.LegalInfo)
	}

	accResponse := CreateAccount(s.T(), accReqPayload)

	accountId := accResponse.Id

	prdConfigRequest := testdata.CreateProductConfigTestCases["Accept_Product_Tnc_No_Doc"].Req
	prdConfigResponse := CreateProductConfig(s.T(), accountId, prdConfigRequest)
	assert.Equal(s.T(), expectedRequirements+expectedOptionalRequirements, len(prdConfigResponse.Requirements))

	stakeholderRequest := testdata.StakeholderTestCases["All_Stakeholder_Fields"].Req
	stakeholderResponse := CreateStakeholder(s.T(), accountId, stakeholderRequest)
	fmt.Println(stakeholderResponse.Id)

	prdConfigId := prdConfigResponse.Id

	prdConfigUpdateRequest := testdata.UpdateProductConfigTestCases["Settlement_Details"].Req
	prdConfigUpdateResponse := UpdateProductConfig(s.T(), accountId, prdConfigId, prdConfigUpdateRequest)
	fmt.Println(prdConfigUpdateResponse)

	prdConfigUpdateRequest = testdata.UpdateProductConfigTestCases["No_Doc_Otp_Details"].Req
	prdConfigUpdateResponse = UpdateProductConfig(s.T(), accountId, prdConfigId, prdConfigUpdateRequest)
	fmt.Println(prdConfigUpdateResponse)

	prdConfigResponse = FetchProductConfig(s.T(), accountId, prdConfigUpdateResponse.Id)
	assert.Equal(s.T(), expectedOptionalRequirements, len(s.fetchRequirementsOfStatus(prdConfigResponse.Requirements, "optional")))
	assert.Equal(s.T(), UnderReview, prdConfigResponse.ActivationStatus)

	return dtos.OnboardingDetails{
		AccountId:       accountId,
		StakeholderId:   stakeholderResponse.Id,
		ProductConfigId: prdConfigResponse.Id,
	}
}

func (s OnboardingV2APISuite) activateNoDocMerchant(accountId string) {
	merchantStatusUpdateRequest := dtos2.ActivationStatusRequest{
		ActivationStatus: ActivatedKycPending,
	}

	merchantId := strings.Split(accountId, "_")[1]
	merchantStatusResponse := impl.UpdateMerchantActivationStatus(s.T(), merchantId, merchantStatusUpdateRequest)
	assert.Equal(s.T(), ActivatedKycPending, merchantStatusResponse.ActivationStatus)
}

func (s OnboardingV2APISuite) fetchRequirementsOfStatus(requirements []dtos.Requirement, status string) []dtos.Requirement {
	var filteredRequirements []dtos.Requirement
	for _, requirement := range requirements {
		if requirement.Status == status {
			filteredRequirements = append(filteredRequirements, requirement)
		}
	}
	return filteredRequirements
}

func TestOnboardingAPIs(t *testing.T) {
	suite.Run(t, &OnboardingV2APISuite{Suite: itf.NewSuite(itf.WithTags([]string{TagOnboardingApi}), itf.WithPriority(itf.PriorityP0))})
}
