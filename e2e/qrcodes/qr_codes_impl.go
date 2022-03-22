package qrcodes

import (
	"encoding/json"
	"github.com/razorpay/api/e2e"
	"net/http"
	"testing"
)

func CreateQrCode(t *testing.T, req QrCodeCreateRequest) QrCodeCreateResponse {
	var qrCodeCreateResponse QrCodeCreateResponse
	Initialize(t)
	obj := qrCodesHost.POST("/v1/payments/qr_codes").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		WithJSON(req).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &qrCodeCreateResponse)
	return qrCodeCreateResponse
}

func CreateQrCodeNegative(t *testing.T, req QrCodeCreateRequest) ErrorResponse {
	var qrCodeCreateErrorResponse ErrorResponse
	Initialize(t)
	obj := qrCodesHost.POST("/v1/payments/qr_codes").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		WithJSON(req).
		Expect().
		Status(http.StatusBadRequest).Body()
	json.Unmarshal([]byte(obj.Raw()), &qrCodeCreateErrorResponse)
	return qrCodeCreateErrorResponse
}

func CloseQrCodePositive(t *testing.T, qrCodeId string) QrCodeCreateResponse {
	var qrCodeCreateResponse QrCodeCreateResponse
	obj := qrCodesHost.POST("/v1/payments/qr_codes/" + qrCodeId + "/close").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &qrCodeCreateResponse)
	return qrCodeCreateResponse
}

func CloseQrCodeNegative(t *testing.T, qrCodeId string) ErrorResponse {
	var errorResponse ErrorResponse
	obj := qrCodesHost.POST("/v1/payments/qr_codes/" + qrCodeId + "/close").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		Expect().
		Status(http.StatusBadRequest).Body()
	json.Unmarshal([]byte(obj.Raw()), &errorResponse)
	return errorResponse
}
