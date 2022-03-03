package e2e

type PaymentPageRequest struct {
	Currency         string             `json:"currency,omitempty"`
	ExpireBy         interface{}        `json:"expire_by"`
	Title            string             `json:"title"`
	Description      string             `json:"description"`
	Terms            string             `json:"terms"`
	SupportEmail     string             `json:"support_email"`
	SupportContact   string             `json:"support_contact"`
	PPSettings       PPSettings          `json:"settings"`
	TemplateType     string             `json:"template_type,omitempty"`
	ViewType         string             `json:"view_type,omitempty"`
	PaymentPageItems []PaymentPageItems `json:"payment_page_items"`
}
type PPSettings struct {
	UdfSchema                 string `json:"udf_schema"`
	Theme                     string `json:"theme"`
	PpButtonDisableBranding   string `json:"pp_button_disable_branding"`
	PaymentButtonTheme        string `json:"payment_button_theme"`
	PaymentButtonText         string `json:"payment_button_text"`
	AllowSocialShare          string `json:"allow_social_share"`
	PaymentSuccessMessage     string `json:"payment_success_message"`
	PaymentSuccessRedirectURL string `json:"payment_success_redirect_url"`
	AllowMultipleUnits		  string `json:"allow_multiple_units"`
}
type PaymentPageItems struct {
	Item        Item      `json:"item,omitempty"`
	Settings    PPItemSettings `json:"settings,omitempty"`
	Stock       int       `json:"stock,omitempty"`
	MinPurchase int       `json:"min_purchase,omitempty"`
	MaxPurchase int       `json:"max_purchase,omitempty"`
	MinAmount   int       `json:"min_amount,omitempty"`
	MaxAmount   int       `json:"max_amount,omitempty"`
}
type Item struct {
	Name        string `json:"name"`
	Currency    string `json:"currency"`
	Amount      int    `json:"amount,omitempty"`
	Description string `json:"description"`
	Type        string `json:"type"`
}
type PPItemSettings struct {
	Position int `json:"position"`
}
