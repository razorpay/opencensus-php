package oneclickcheckout

import (
	"encoding/json"
	"reflect"
	"testing"

	"github.com/stretchr/testify/assert"
)

func Equal(vx, vy interface{}) bool {
	if reflect.TypeOf(vx) != reflect.TypeOf(vy) {
		return false
	}

	switch x := vx.(type) {
	case map[string]interface{}:
		y := vy.(map[string]interface{})
		for k, v := range x {
			val2 := y[k]

			if (v == nil) != (val2 == nil) {
				return false
			}

			if !Equal(v, val2) {
				return false
			}
		}
		return true
	case []interface{}:
		y := vy.([]interface{})
		if len(x) != len(y) {
			return false
		}
		var matches int
		flagged := make([]bool, len(y))
		for _, v := range x {
			for i, v2 := range y {
				if Equal(v, v2) && !flagged[i] {
					matches++
					flagged[i] = true

					break
				}
			}
		}
		return matches == len(x)
	default:
		return vx == vy
	}
}

func ValidateResponse(t *testing.T, expectedRes, actualRes []byte) {
	var expected, actual interface{}
	json.Unmarshal(expectedRes, &expected)
	json.Unmarshal(actualRes, &actual)
	assert.True(t, Equal(expected, actual))
}
