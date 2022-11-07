package e2e

import (
	"bytes"
	"encoding/json"
	"github.com/razorpay/dashboard/e2e"
	"net/http"
	"net/url"
)

func getTokenBeforeLogin() (string, string) {
	result := [2]string{}

	req, err := http.NewRequest(GET_METHOD, e2e.Config.App.Hostname+PATH_ORG, nil)

	if err != nil {
		return "", ""
	}

	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)

	resp, err := http.DefaultClient.Do(req)

	if err != nil {
		return "", ""
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			result[0], _ = url.QueryUnescape(cookie.Value)
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			result[1] = cookie.Value
		}
	}

	return result[0], result[1]
}

func getTokenAfterLogin(loginCreds map[string]string) (string, string) {
	result := [2]string{}

	req, err := http.NewRequest(GET_METHOD, e2e.Config.App.Hostname+PATH_ORG, nil)

	if err != nil {
		return "", ""
	}

	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)

	resp, err := http.DefaultClient.Do(req)

	if err != nil {
		return "", ""
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			result[0], _ = url.QueryUnescape(cookie.Value)
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			result[1] = cookie.Value
		}
	}

	json_data, err := json.Marshal(loginCreds)

	if err != nil {
		return "", ""
	}

	req, err = http.NewRequest(POST_METHOD, e2e.Config.App.Hostname+PATH_USER_SIGNIN, bytes.NewBuffer(json_data))

	if err != nil {
		return "", ""
	}

	req.Header.Set(HEADER_CONTENT_TYPE, APPLICATION_JSON)
	req.Header.Set(HEADER_X_XSRF_TOKEN, result[0])
	req.Header.Set(e2e.DevstackLabelHeader, e2e.DevServeHeader)

	cookie := http.Cookie{Name: COOKIE_RZP_USR_SESSION, Value: result[1]}
	req.AddCookie(&cookie)

	resp, err = http.DefaultClient.Do(req)

	if err != nil {
		return "", ""
	}

	for _, cookie := range resp.Cookies() {
		if cookie.Name == COOKIE_XSRF_TOKEN {
			result[0], _ = url.QueryUnescape(cookie.Value)
		}

		if cookie.Name == COOKIE_RZP_USR_SESSION {
			result[1] = cookie.Value
		}
	}

	return result[0], result[1]
}
