package virtualaccount

import (
	"encoding/json"
	"fmt"
	"github.com/razorpay/api/e2e"
	"net/http"
	"testing"
)

// Create VA
func CreateVirtualAccount(t *testing.T, virtualAccountReq VirtualAccountRequest) VirtualAccountResponse {
	var virtualAccountRes VirtualAccountResponse
	Initialize(t)
	obj := virtualAccountHost.POST("/v1/virtual_accounts").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		WithJSON(virtualAccountReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountRes)
	return virtualAccountRes
}
// Create VA
func CreateVirtualAccountNegative(t *testing.T, virtualAccountReq VirtualAccountRequest) ErrorResponse {
	Initialize(t)
	var virtualAccountRes ErrorResponse
	obj := virtualAccountHost.POST("/v1/virtual_accounts").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		WithJSON(virtualAccountReq).
		Expect().
		Status(http.StatusBadRequest).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountRes)
	return virtualAccountRes
}
// Update VA
func UpdateVirtualAccount(t *testing.T,virtualAccountRes VirtualAccountResponse) VirtualAccountResponse {
	var virtualAccountReq VirtualAccountRequest
	virtualAccountReq.Name="TestUpdateName"
	virtualAccountReq.Description="TestUpdateDescription"
	Initialize(t)
	obj := virtualAccountHost.PATCH(fmt.Sprintf("/v1/virtual_accounts/%s",virtualAccountRes.ID)).
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		WithJSON(virtualAccountReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountRes)
	return virtualAccountRes
}
