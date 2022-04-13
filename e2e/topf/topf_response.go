package e2e

type RegisterUserResponse struct {
	StatusCode int                   `json:"status_code"`
	Success    bool                  `json:"success"`
	Data       RegisterUserDataEmail `json:"data"`
}

type RegisterUserVerifyOtpResponse struct {
	StatusCode int                    `json:"status_code"`
	Success    bool                   `json:"success"`
	Data       RegisterUserDataMobile `json:"data"`
}

type RegisterUserDataEmail struct {
	Email string `json:"email""`
}

type RegisterUserDataMobile struct {
	ContactMobile string `json:"contact_mobile""`
}

type LoginSignUpUserSendOtpResponse struct {
	StatusCode int                        `json:"status_code"`
	Success    bool                       `json:"success"`
	Data       LoginSignUpUserSendOtpData `json:"data"`
}

type LoginSignUpUserSendOtpData struct {
	Token string `json:"token"`
}

type LoginResponse struct {
	StatusCode int               `json:"status_code"`
	Success    bool              `json:"success"`
	Data       LoginResponseData `json:"data"`
}

type LoginResponseData struct {
	LoggedInVia string `json:"logged_in_via"`
}
