package e2e

type ErrorResponse struct {
	Error struct {
		Code        string `json:"code"`
		Description string `json:"description"`
		Source      string `json:"source"`
		Step        string `json:"step"`
		Reason      string `json:"reason"`
		Metadata    struct {
		} `json:"metadata"`
	} `json:"error"`
}
