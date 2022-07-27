package oneclickcheckout

type ErrorResponse struct {
	Error *Error `json:"error,omitempty"`
}

type Error struct {
	Code        string `json:"code,omitempty"`
	Description string `json:"description,omitempty"`
	Source      string `json:"source,omitempty"`
	Step        string `json:"step,omitempty"`
	Reason      string `json:"reason,omitempty"`
	Metadata    struct {
	} `json:"metadata,omitempty"`
	Field string `json:"field,omitempty"`
}
