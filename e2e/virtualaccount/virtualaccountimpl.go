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
// Fetch Va by parameter
func FetchAllVirtualAccountParameter(t *testing.T) VirtualAccountEntityResponse {
	var virtualAccountRes VirtualAccountEntityResponse
	Initialize(t)
	obj := virtualAccountHost.GET("/v1/virtual_accounts").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithQueryObject(query).
		WithHeaders(header).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountRes)
	return virtualAccountRes
}
// create order
func CreateOrder(t *testing.T, orderReq OrderRequest) OrderResponse {
	var orderRes OrderResponse
	Initialize(t)
	obj := virtualAccountHost.POST("/v1/orders").
		WithBasicAuth(e2e.Config.VirtualAccount.Username, e2e.Config.VirtualAccount.Password).
		WithHeaders(header).
		WithJSON(orderReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &orderRes)
	return orderRes
}

// create order
func CreateVAForOrder(t *testing.T, orderReq OrderVARequest,response OrderResponse) VirtualAccountResponse {
	var virtualAccountResponse VirtualAccountResponse
	Initialize(t)
	obj := virtualAccountHost.POST(fmt.Sprintf("/v1/orders/%s/virtual_accounts",response.ID)).
		WithBasicAuth(e2e.Config.VirtualAccount.Username,"").
		WithHeaders(header).
		WithJSON(orderReq).
		Expect().
		Status(http.StatusOK).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountResponse)
	return virtualAccountResponse
}

func CreateVAForOrderNegative(t *testing.T, orderReq OrderVARequest,response OrderResponse) ErrorResponse {
	var virtualAccountResponse ErrorResponse
	Initialize(t)
	obj := virtualAccountHost.POST(fmt.Sprintf("/v1/orders/%s/virtual_accounts",response.ID)).
		WithBasicAuth(e2e.Config.VirtualAccount.Username,"").
		WithHeaders(header).
		WithJSON(orderReq).
		Expect().
		Status(http.StatusBadRequest).Body()
	json.Unmarshal([]byte(obj.Raw()), &virtualAccountResponse)
	return virtualAccountResponse
}

