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
//Close Virtual Account
func CloseVirtualAccount(t *testing.T,virtualAccountRes VirtualAccountResponse) VirtualAccountResponse {
	Initialize(t)
	obj := virtualAccountHost.POST(fmt.Sprintf("/v1/virtual_accounts/%s/close",virtualAccountRes.ID)).
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountRes)
	return virtualAccountRes
}
// Fetch Virtual Account by Id
func FetchVirtualAccount(t *testing.T,virtualAccountRes VirtualAccountResponse) VirtualAccountResponse {
	Initialize(t)
	obj := virtualAccountHost.GET(fmt.Sprintf("/v1/virtual_accounts/%s",virtualAccountRes.ID)).
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountRes)
	return virtualAccountRes
}

// Fetch all Virtual Accounts
func FetchAllVirtualAccount(t *testing.T) VirtualAccountEntityResponse {
	var virtualAccountRes VirtualAccountEntityResponse
	Initialize(t)
	obj := virtualAccountHost.GET("/v1/virtual_accounts").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountRes)
	return virtualAccountRes
}

