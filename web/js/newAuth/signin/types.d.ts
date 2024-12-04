export type OrgData = Partial<{
  id: string;
  display_name: string;
  business_name: string;
  email: string;
  email_domains: string[];
  allow_sign_up: boolean;
  login_logo_url: string;
  main_logo_url: string;
  invoice_logo_url: string;
  checkout_logo_url: string;
  email_logo_url: string;
  auth_type: string;
  created_at: number;
  custom_code: string;
  from_email: string;
  signature_email: string;
  default_pricing_plan_id: string;
  background_image_url: string;
  merchant_styles?: MerchantStyles;
  merchant_second_factor_auth: number;
  merchant_max_wrong_2fa_attempts: number;
  admin_second_factor_auth: number;
  admin_max_wrong_2fa_attempts: number;
  second_factor_auth_mode: string;
  payment_apps_logo_url: string;
  payment_btn_logo_url: string;
  features: string[];
  hostname: string;
  isjkOrg?: boolean;
}>;

export type MerchantStyles = Partial<{
  navBg: string;
  primary: string;
  sideBarIcon: string;
  checkout_theme_color: string;
}>;

export type TransformedOrgData = ReturnType<typeof import('../apis').transformFetchOrgData>;
