package e2e

import (
	"bytes"
	"encoding/json"
	"github.com/razorpay/dashboard/e2e"
	"net/http"
)

func getToken() (string, string) {
	result := [2]string{}
	resp, err := http.Get(e2e.Config.App.Hostname + PATH_ORG)

	if err != nil {
		return "", ""
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			result[0] = cookie.Value
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			result[1] = cookie.Value
		}
	}

	values := map[string]string{
		EMAIL:    DEVSTACK_TEST_EMAIL,
		PASSWORD: DEVSTACK_TEST_PASSWORD,
		CAPTCHA:  DEVSTACK_TEST_CAPTCHA,
	}

	json_data, err := json.Marshal(values)

	if err != nil {
		return "", ""
	}

	resp, err = http.Post(e2e.Config.App.Hostname+PATH_USER_SIGNIN, APPLICATION_JSON,
		bytes.NewBuffer(json_data))

	req, err := http.NewRequest(POST_METHOD, e2e.Config.App.Hostname+PATH_USER_SIGNIN, bytes.NewBuffer(json_data))
	if err != nil {
		return "", ""
	}
	req.Header.Set(HEADER_CONTENT_TYPE, APPLICATION_JSON)
	req.Header.Set(HEADER_X_XSRF_TOKEN, result[0])
	cookie := http.Cookie{Name: COOKIE_RZP_USR_SESSION, Value: result[1]}

	req.AddCookie(&cookie)

	resp, err = http.DefaultClient.Do(req)

	if err != nil {
		return "", ""
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			result[0] = cookie.Value
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			result[1] = cookie.Value
		}
	}

	return result[0], result[1]
}
