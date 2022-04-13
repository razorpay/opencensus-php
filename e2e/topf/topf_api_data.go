package e2e

import (
	"github.com/razorpay/goutils/itf/httpexpect"
	"net/http"
)

var email = GenerateUniqueEmail()
var contactMobile = GenerateUniqueMobileNumber()

var requestTestForRegister = map[string]*httpexpect.RequestTest{
	"ValidRequest": {
		Method: http.MethodPost,
		Path:   "/user/register",
		Body: &LoginSignUpUserRequest{
			Email:    email,
			Password: "Abc123456",
			Captcha:  "Faked",
		},
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: &RegisterUserResponse{
			StatusCode: 200,
			Success:    true,
			Data: RegisterUserDataEmail{
				Email: email,
			},
		},
	},
}

var requestRegisterOtpSend = map[string]*httpexpect.RequestTest{
	"ValidRequest": {
		Method: http.MethodPost,
		Path:   "/user/register/otp",
		Body: &LoginSignUpUserSendOtpRequest{
			ContactMobile: contactMobile,
			Token:         "J3sOkv1CNFneH6",
		},
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: &LoginSignUpUserSendOtpResponse{
			StatusCode: 200,
			Success:    true,
			Data: LoginSignUpUserSendOtpData{
				Token: "J3sOkv1CNFneH6",
			},
		},
	},
}

var requestRegisterOtpVerify = map[string]*httpexpect.RequestTest{
	"ValidRequest": {
		Method: http.MethodPost,
		Path:   "/user/register/otp/verify",
		Body: &LoginSignUpUserVerifyOtpRequest{
			ContactMobile: contactMobile,
			Captcha:       "Faked",
			Otp:           "000007",
			Token:         "J3sOkv1CNFneH6",
		},
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: &RegisterUserVerifyOtpResponse{
			StatusCode: 200,
			Success:    true,
			Data: RegisterUserDataMobile{
				ContactMobile: contactMobile,
			},
		},
	},
}

var requestTestForLogin = map[string]*httpexpect.RequestTest{
	"ValidRequest": {
		Method: http.MethodPost,
		Path:   "/user/signin",
		Body: &LoginSignUpUserRequest{
			Email:    "piyush.verma+35015@razorpay.com",
			Password: "Pi123456",
			Captcha:  "Faked",
		},
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: &LoginResponse{
			StatusCode: 200,
			Success:    true,
			Data: LoginResponseData{
				LoggedInVia: "email",
			},
		},
	},
}

var requestLoginOtpSend = map[string]*httpexpect.RequestTest{
	"ValidRequest": {
		Method: http.MethodPost,
		Path:   "/user/signin/otp",
		Body: &LoginSignUpUserSendOtpRequest{
			ContactMobile: "7698856848",
			Token:         "J3sOkv1CNFneH6",
		},
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: &LoginSignUpUserSendOtpResponse{
			StatusCode: 200,
			Success:    true,
			Data: LoginSignUpUserSendOtpData{
				Token: "J3sOkv1CNFneH6",
			},
		},
	},
}

var requestLoginOtpVerify = map[string]*httpexpect.RequestTest{
	"ValidRequest": {
		Method: http.MethodPost,
		Path:   "/user/signin/otp/verify",
		Body: &LoginSignUpUserVerifyOtpRequest{
			ContactMobile: "7698856848",
			Captcha:       "Faked",
			Otp:           "000007",
			Token:         "J3sOkv1CNFneH6",
		},
		ExpectedResponseCode: http.StatusOK,
		ExpectedResponseBody: &LoginResponse{
			StatusCode: 200,
			Success:    true,
			Data: LoginResponseData{
				LoggedInVia: "contact_mobile",
			},
		},
	},
}
