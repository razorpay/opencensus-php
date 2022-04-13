package e2e

type LoginSignUpUserRequest struct {
	Email    string `json:"email"`
	Password string `json:"password"`
	Captcha  string `json:"captcha"`
}

type LoginSignUpUserSendOtpRequest struct {
	ContactMobile string `json:"contact_mobile"`
	Token         string `json:"token"`
}

type LoginSignUpUserVerifyOtpRequest struct {
	ContactMobile string `json:"contact_mobile"`
	Token         string `json:"token"`
	Captcha       string `json:"captcha"`
	Otp           string `json:"otp"`
}
