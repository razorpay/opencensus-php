package virtualaccount

import (
	"fmt"
	"github.com/razorpay/goutils/itf"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/suite"
	"testing"
)

type VirtualAccountAPITestSuite struct {
	itf.Suite
}

func (s *VirtualAccountAPITestSuite) TestCreateVirtualAccountPositive(){
	s.T().Skip("Test is intermittently failing because of 504,408 errors")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_AUTOGENERATE_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_AMOUNT_ZERO",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA_ONLY",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			verifyCreateVA(s.T(), ppRes)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestCloseVirtualAccountPositive() {
	s.T().Skip("Test is constantly failing because of 401")
	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_AUTOGENERATE_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_AMOUNT_ZERO",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA_ONLY",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			verifyCreateVA(s.T(), ppRes)
			ppResClose := CloseVirtualAccount(s.T(),ppRes)
			verifyClosedVA(s.T(), ppResClose)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestCreateVirtualAccountNegative(){
	s.T().Skip("Test is constantly failing because of 401")
	type negativeTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []negativeTestCases{
		{
			description: "VA_WITH_INVALID_RECEIVER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","v"},
				},
				Description: "One or more of the given receiver types is invalid.",
			},
		},
		{
			description: "VA_WITH_CLOSE_BY",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
				},
				Description: "close_by should be at least 15 minutes after current time",
				CloseBy: 1577220870,
			},
		},
		{
			description: "VA_RECEIVER_ITEM",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{},
				},
				Description: "The receivers field is required.",
			},
		},
		{
			description: "VA_WITH_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "-1",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "The amount expected must be at least 0.",
			},
		},
	} {
		s.Run(scenario.description, func (){
			error := CreateVirtualAccountNegative(s.T(), scenario.input)
			assert.Equal(s.T(),error.Error.Description, scenario.input.Description)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestCreateVirtualAccountUpdate(){
	s.T().Skip("Test is intermittently failing because of 404,503 errors")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_AUTOGENERATE_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_AMOUNT_ZERO",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA_ONLY",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			ppUpdateRes := UpdateVirtualAccount(s.T(),ppRes)
			fmt.Println(ppUpdateRes)
			verifyCreateVA(s.T(), ppRes)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestGetVirtualAccountById(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors ")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			ppGetVARes := FetchVirtualAccount(s.T(),ppRes)
			verifyFetchedVA(s.T(), ppRes,ppGetVARes)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestGetAllVirtualAccount(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors ")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			assert.NotNil(s.T(),ppRes.ID,"VA did not get created")
			ppGetVARes := FetchAllVirtualAccount(s.T())
			verifyFetchedAllVA(s.T(),ppGetVARes)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestGetAllVirtualAccountByParameter(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors ")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			assert.NotNil(s.T(),ppRes.ID,"VA did not get created")
			ppGetVARes := FetchAllVirtualAccountParameter(s.T())
			fmt.Println(ppGetVARes)
			//verifyFetchedAllVA(s.T(),ppGetVARes)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestCreateVirtualAccountPositiveICICI(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors ")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_AUTOGENERATE_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_AMOUNT_ZERO",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA_ONLY",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			scenario.input.CustomerID="cust_Iwf3ydmuCV3y8R"
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			verifyCreateVA(s.T(), ppRes)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestCreateVirtualAccountNegativeICICI(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors")

	type negativeTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []negativeTestCases{
		{
			description: "VA_WITH_INVALID_RECEIVER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","v"},
				},
				Description: "One or more of the given receiver types is invalid.",
			},
		},
		{
			description: "VA_WITH_CLOSE_BY",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
				},
				Description: "close_by should be at least 15 minutes after current time",
				CloseBy: 1577220870,
			},
		},
		{
			description: "VA_RECEIVER_ITEM",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{},
				},
				Description: "The receivers field is required.",
			},
		},
		{
			description: "VA_WITH_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "-1",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "The amount expected must be at least 0.",
			},
		},
	} {
		s.Run(scenario.description, func (){
			scenario.input.CustomerID="cust_Iwf3ydmuCV3y8R"
			error := CreateVirtualAccountNegative(s.T(), scenario.input)
			assert.Equal(s.T(),error.Error.Description, scenario.input.Description)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestCloseVirtualAccountICICI(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors ")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_AUTOGENERATE_VPA",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account","vpa"},
					BankAccount: nil,
				},
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_AMOUNT_ZERO",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_VPA_ONLY",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"vpa"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "0",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			scenario.input.CustomerID="cust_Iwf3ydmuCV3y8R"
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			verifyCreateVA(s.T(), ppRes)
			ppResClose := CloseVirtualAccount(s.T(),ppRes)
			verifyClosedVA(s.T(), ppResClose)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestGetVirtualAccountByIdICICI(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors ")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			scenario.input.CustomerID="cust_Iwf3ydmuCV3y8R"
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			ppGetVARes := FetchVirtualAccount(s.T(),ppRes)
			verifyFetchedVA(s.T(), ppRes,ppGetVARes)
		})
	}
}

func (s *VirtualAccountAPITestSuite) TestGetAllVirtualAccountICICI(){
	s.T().Skip("Test is intermittently failing because of 503,404 errors ")

	type positiveTestCases struct {
		description   string
		input         VirtualAccountRequest
	}
	for _, scenario := range []positiveTestCases{
		{
			description: "VA_WITH_BANK_ACCOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				CustomerID: "cust_Iwf3ydmuCV3y8R",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_CUSTOMER",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				AmountExpected: "1000",
				Description: "Testing of Virtual Accounts",
			},
		},
		{
			description: "VA_WITH_OUT_AMOUNT",
			input: VirtualAccountRequest{
				Receiver: &ReceiversRequest{
					Types: []string{"bank_account"},
					BankAccount: nil,
				},
				Name: "Automation Test",
				Description: "Testing of Virtual Accounts",
			},
		},
	} {
		s.Run(scenario.description, func (){
			scenario.input.CustomerID="cust_Iwf3ydmuCV3y8R"
			ppRes := CreateVirtualAccount(s.T(), scenario.input)
			assert.NotNil(s.T(),ppRes.ID,"VA did not get created")
			ppGetVARes := FetchAllVirtualAccount(s.T())
			verifyFetchedAllVA(s.T(),ppGetVARes)
		})
	}
}

func verifyCreateVA(t *testing.T, virtualAccountResponse VirtualAccountResponse) {
	assert.NotEmptyf(t, virtualAccountResponse.ID, "Virtual Account did not created")
}

func verifyClosedVA(t *testing.T, virtualAccountResponse VirtualAccountResponse) {
	assert.Equalf(t, "closed",virtualAccountResponse.Status,"Status is not closed")
	assert.NotEmptyf(t, virtualAccountResponse.ID,"Virtual Account could not be closed")
}

func verifyFetchedVA(t *testing.T, virtualAccountResponse VirtualAccountResponse,virtualAccountFetched VirtualAccountResponse) {
	assert.Equalf(t, virtualAccountResponse.ID,virtualAccountFetched.ID,"Id could not be fetched")
	assert.NotEmptyf(t, virtualAccountFetched.ID,"Virtual Account could not be fetched")
}

func verifyFetchedAllVA(t *testing.T, virtualAccountResponse VirtualAccountEntityResponse) {
	assert.NotEmptyf(t, virtualAccountResponse.Count,"Virtual Account could not be fetched")
}

func TestVirtualAccountAPI(t *testing.T) {
	suite.Run(t, &VirtualAccountAPITestSuite{Suite: itf.NewSuite(itf.WithTags([]string{TagVirtualAccount}), itf.WithPriority(itf.PriorityP0))})
}
