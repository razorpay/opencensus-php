package transfer

import (
	"encoding/json"
	"net/http"
	"testing"

	"github.com/razorpay/api/e2e"
)

func CreateDirectTransferNegative(t *testing.T, transferReqData CreateDirectTransferRequest) ErrorResponse {
	Initialize(t)
	var errorResponse ErrorResponse
	obj := transferHost.POST("/v1/transfers").
		WithBasicAuth(e2e.Config.SubMerchant.LiveUsername, e2e.Config.SubMerchant.Password).
		WithHeaders(header).
		WithJSON(transferReqData).
		Expect().
		Status(http.StatusBadRequest).Body()

	json.Unmarshal([]byte(obj.Raw()), &errorResponse)
	return errorResponse
}

func CreateDirectTransferPositive(t *testing.T, transferReqData CreateDirectTransferRequest) CreateDirectTransferResponse {
	Initialize(t)
	var res CreateDirectTransferResponse
	obj := transferHost.POST("/v1/transfers").
		WithBasicAuth(e2e.Config.SubMerchant.Username, e2e.Config.SubMerchant.Password).
		WithHeaders(header).
		WithJSON(transferReqData).
		Expect().
		Status(http.StatusOK).Body()

	json.Unmarshal([]byte(obj.Raw()), &res)
	return res
}
