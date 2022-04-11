package linked_account_activation

import (
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"strconv"
	"strings"
	"testing"
	"time"

	"github.com/razorpay/api/e2e"
	"github.com/stretchr/testify/assert"
)

var BankAccountToBvsValidationStatusMap = map[string]string{
	"1234567890": "success",
	"1234567892": "failed",
	"1234567893": "success",
}

type HoldFundsData struct {
	HoldFunds       int
	HoldFundsReason string
}

func SendMockBvsValidationEvent(t *testing.T, laReq LinkedAccountCreateRequest, validationId string) {
	Initialize(t)
	mockBvsValidationData := GetMockBvsValidationData(laReq.BankAccount.AccountNumber, validationId)
	linkedAccountHost.POST("/v1/mock-bvs-validation").
		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
		WithHeaders(header).
		WithJSON(mockBvsValidationData).
		Expect().
		Status(http.StatusOK)
}

func GetMockBvsValidationData(AccountNumber string, validationId string) MockBVSValidationEventRequest {
	var mockBvsRequestData MockBVSValidationEventRequest
	validationStatus := BankAccountToBvsValidationStatusMap[AccountNumber]
	if validationStatus == "success" {
		mockBvsRequestData = MockBVSValidationEventRequest{
			Data: MockBvsRequestData{
				ValidationId:     validationId,
				ErrorCode:        "",
				ErrorDescription: "",
				Status:           validationStatus,
			}}
	} else if validationStatus == "failed" {
		mockBvsRequestData = MockBVSValidationEventRequest{
			Data: MockBvsRequestData{
				ValidationId:     validationId,
				ErrorCode:        "INPUT_DATA_ISSUE",
				ErrorDescription: "invalid data submitted",
				Status:           validationStatus,
			}}
	}
	return mockBvsRequestData
}

func getValidationStatusAndAssert(t *testing.T, status string, validationId string) {
	var validationStatus string
	var maxRetries int = 10
	if status == "failed" {
		maxRetries = 4
	}
	for i := 0; i < maxRetries; i++ {
		fmt.Println("Fetching validation Status,select query ::", fmt.Sprintf(GetBvsValidationStatusSelectQuery, validationId))
		e2e.ApiDB.Instance(context.Background()).
			Raw(fmt.Sprintf(GetBvsValidationStatusSelectQuery, validationId)).
			Scan(&validationStatus)
		if len(validationStatus) > 0 {
			break
		} else if i == (maxRetries - 1) {
			assert.Fail(t, "Could not fetch validation status.")
		}
		time.Sleep(time.Duration(maxRetries * int(time.Second)))
	}
	assert.Equal(t, validationStatus, status)
}

func GetBvsValidationIdWithOwnerId(merchantId string) string {
	var bvsValidationId string
	ownerId := strings.Trim(merchantId, "acc_")
	bvsValidationSelectQuery := fmt.Sprintf(GetBvsValidationIdSelectQuery, ownerId)
	fmt.Println("Fetching validation Id,select query :: ", bvsValidationSelectQuery)
	e2e.ApiDB.Instance(context.Background()).
		Raw(bvsValidationSelectQuery).
		Scan(&bvsValidationId)
	return bvsValidationId
}

func GetHoldFundsData(merchantId string) HoldFundsData {
	var holdFundsData HoldFundsData
	trimmedMerchantId := strings.Trim(merchantId, "acc_")
	selectHoldFundsDataQuery := fmt.Sprintf(GetHoldFundsDataQuery, trimmedMerchantId)
	e2e.ApiDB.Instance(context.Background()).
		Raw(selectHoldFundsDataQuery).
		Scan(&holdFundsData)
	return holdFundsData
}

func CreateLinkedAccount(t *testing.T, laReq LinkedAccountCreateRequest) LinkedAccountCreateResponse {
	Initialize(t)
	var laRes LinkedAccountCreateResponse
	uniqueId := strconv.FormatInt(time.Now().Unix(), 10)
	laReq.Email = "la-" + uniqueId + "@email.com"
	obj := linkedAccountHost.POST("/v1/beta/accounts").
		WithBasicAuth(e2e.Config.PaymentPage.Username, e2e.Config.PaymentPage.Password).
		WithHeaders(header).
		WithJSON(laReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &laRes)
	return laRes
}

func FetchMerchantActivationDetails(t *testing.T, linkedAccount LinkedAccountCreateResponse) MerchantActivationDetails {
	Initialize(t)
	var laRes MerchantActivationDetails
	res := linkedAccountHost.GET("/v1/merchant/activation").
		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
		WithHeaders(map[string]string{
			"X-Razorpay-Account":    linkedAccount.Id,
			"X-Dashboard-User-id":   e2e.Config.SubMerchant.User,
			"X-Dashboard-User-Role": e2e.Config.SubMerchant.Role,
		}).
		Expect().
		Status(http.StatusOK).Body()

	json.Unmarshal([]byte(res.Raw()), &laRes)
	return laRes
}

func UpdateLinkedAccountBankDetails(t *testing.T, merchantId string, bankDetailsUpdateReq BankDetailsUpdateRequest) BankDetailsUpdateResponse {
	Initialize(t)
	var bankDetailsUpdateRes BankDetailsUpdateResponse
	updateUrl := fmt.Sprintf("/v1/linked_accounts/%s/bank_account", merchantId)
	res := linkedAccountHost.POST(updateUrl).
		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
		WithHeaders(map[string]string{
			"X-Dashboard-User-id":   e2e.Config.SubMerchant.User,
			"X-Dashboard-User-Role": e2e.Config.SubMerchant.Role,
		}).
		WithJSON(bankDetailsUpdateReq).
		Expect().
		Status(http.StatusOK).Body()

	json.Unmarshal([]byte(res.Raw()), &bankDetailsUpdateRes)
	return bankDetailsUpdateRes
}
