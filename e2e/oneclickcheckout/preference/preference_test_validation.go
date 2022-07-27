package preference

import (
	"encoding/json"
	"testing"

	"github.com/razorpay/api/e2e/oneclickcheckout"
)

func TestGetPreferences(t *testing.T, getPreferenceTest GetPreferenceTest) GetPreferenceTest {
	preferenceRes := GetPreferences(t, getPreferenceTest.OrderId)
	var actualRes PreferencesResponse
	if getPreferenceTest.Response != nil {
		expected, _ := json.Marshal(getPreferenceTest.Response)
		oneclickcheckout.ValidateResponse(t, expected, preferenceRes)
		json.Unmarshal(preferenceRes, &actualRes)
		getPreferenceTest.Response = &actualRes
	}
	return getPreferenceTest
}
