package linked_account_activation

import (
	"encoding/json"
	"io/ioutil"
)

func GetLinkedAccountCreateRequest(fixtureName string) (LinkedAccountCreateRequest, error) {
	var r LinkedAccountCreateRequest
	bytes, err := ioutil.ReadFile(fixtureName)
	if err != nil {
		return r, err
	}
	err = json.Unmarshal(bytes, &r)
	return r, err
}

const (
	TagLinkedAccount = "linkedaccount"
	GetBvsValidationIdSelectQuery = "SELECT validation_id FROM bvs_validation WHERE  owner_id = '%s' AND owner_type = 'merchant' AND artefact_type = 'bank_account'"
	GetBvsValidationStatusSelectQuery = "SELECT validation_status FROM bvs_validation WHERE  validation_id = '%s' AND owner_type = 'merchant' AND artefact_type = 'bank_account'"
	GetHoldFundsDataQuery = "SELECT hold_funds,hold_funds_reason from merchants where id='%s'"
)
