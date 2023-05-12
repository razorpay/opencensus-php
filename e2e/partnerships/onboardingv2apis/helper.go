package e2e

import (
	"context"
	"fmt"
	"github.com/razorpay/api/e2e"
	linked_account_activation "github.com/razorpay/api/e2e/linkedaccountactivation"
)

func GetBvsValidationIdWithOwnerIdForArtefact(merchantId string, artefactType string) string {
	var bvsValidationId string
	bvsValidationSelectQuery := fmt.Sprintf(GetBvsValidationIdSelectQueryForArtefact, merchantId, artefactType)
	fmt.Println("Fetching validation Id,select query :: ", bvsValidationSelectQuery)
	e2e.ApiDB.Instance(context.Background()).
		Raw(bvsValidationSelectQuery).
		Scan(&bvsValidationId)
	return bvsValidationId
}

func GetBvsValidationData(validationId string, status string) linked_account_activation.MockBVSValidationEventRequest {
	if status == "success" {
		return linked_account_activation.MockBVSValidationEventRequest{
			Data: linked_account_activation.MockBvsRequestData{
				ValidationId:     validationId,
				ErrorCode:        "",
				ErrorDescription: "",
				Status:           "success",
			}}
	} else {
		return linked_account_activation.MockBVSValidationEventRequest{
			Data: linked_account_activation.MockBvsRequestData{
				ValidationId:     validationId,
				ErrorCode:        "INPUT_DATA_ISSUE",
				ErrorDescription: "invalid data submitted",
				Status:           "failed",
			}}
	}
}
