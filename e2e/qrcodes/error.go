package qrcodes

type ErrorResponse struct {
	Error *Error `json:"error"`
}

type Error struct {
	Code        string `json:"code"`
	Description string `json:"description"`
	Source      string `json:"source"`
	Step        string `json:"step"`
	Reason      string `json:"reason"`
	Field       string `json:"field"`
}
