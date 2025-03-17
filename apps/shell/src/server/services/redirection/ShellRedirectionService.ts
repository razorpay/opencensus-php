import { Request, Response } from 'express';
import { isBusinessTypeForNotRegisteredBusiness } from '@apps/shell/src/server/utils/businessType';
import { v4 as uuid } from 'uuid';
import { AppConstants } from '@apps/shell/src/server/constants';
import { switchCurrentMerchant } from '@apps/shell/src/server/services/apis/switchCurrentMerchant';

const ttl = 12 * 60 * 60 * 1000;

export class ShellRedirectionService {
  request: Request;
  response: Response;
  data: any;

  constructor(request: Request, response: Response) {
    this.request = request;
    this.response = response;
    this.data = this.response?.locals || {};
  }

  async getShellRedirectionData(
    options: any = {},
  ): Promise<{ destination: string; redirectTo?: string | null }> {
    const domainBasedRedirection = this.getDomainBasedRedirectionUrlIfApplicable();

    if (domainBasedRedirection || this.isAuthSourceWebsite()) {
      return {
        destination: AppConstants.DESTINATION_PHP_BE,
        redirectTo: domainBasedRedirection,
      };
    }

    const currentMerchant = this.data?.user?.current;

    if (!Boolean(currentMerchant)) {
      this.request.shellLogger.warn({
        moduleName: '@shellRedirectionService',
        message: 'No merchant id found',
      });

      return { destination: AppConstants.DESTINATION_PHP_BE };
    }

    const preSignupComplete = (this.data?.user?.pre_signup_complete as boolean) || false;

    const redirectUrlBasedOnUserPersona = await this.getRedirectUrlBasedOnUserPersona();

    if (redirectUrlBasedOnUserPersona) {
      return {
        destination: AppConstants.DESTINATION_SHELL,
        redirectTo: redirectUrlBasedOnUserPersona,
      };
    }

    const user = this.data?.user?.user || {};
    const isConfirmed = (user.confirmed as boolean) || false;
    const isMobileConfirmed = (user.contact_mobile_verified as boolean) || false;
    const isLinkedAccount = this.data?.pre_signup?.linked_account === true;

    const isRedirectApplicable =
      (isConfirmed || isMobileConfirmed || isLinkedAccount) && preSignupComplete;

    return {
      destination: isRedirectApplicable
        ? AppConstants.DESTINATION_SHELL
        : AppConstants.DESTINATION_PHP_BE,
    };
  }

  private getDomainBasedRedirectionUrlIfApplicable(): string | null {
    const domain = this.request.hostname;

    if (AppConstants.DomainRedirectMap[domain]) {
      const { id, redirect_url } = AppConstants.DomainRedirectMap[domain];
      if (this.isDomainRedirectionEnabled(id)) {
        return redirect_url;
      }
    }

    return null;
  }

  private isExperimentOnAndIsUnregisteredBusinessType(): boolean {
    const businesType = this.data.preSignUpDetails?.businessType;

    if (!businesType) {
      return false;
    }

    return isBusinessTypeForNotRegisteredBusiness(businesType);
  }

  private async getRedirectUrlBasedOnUserPersona(): Promise<string | null> {

    if (this.isRedirectionApplicable()) {
      let redirectionURL = process.env[AppConstants.EASY_DASHBOARD_URL] || '';

      if (this.data?.user?.user?.signup_campaign === AppConstants.I18N_MY_SIGNUP) {
        redirectionURL = process.env[AppConstants.EASY_DASHBOARD_CURLEC_URL] || '';
      }

      if (this.data?.user?.user?.signup_campaign === AppConstants.SG_SIGNUP) {
        redirectionURL = process.env[AppConstants.EASY_DASHBOARD_SG_URL] || '';
      }

      this.response.cookie(AppConstants.RZP_MERCHANT_ID, this.data?.user?.id, { maxAge: ttl });
      this.response.cookie(AppConstants.RZP_USER_ID, this.data?.user?.user?.id, { maxAge: ttl });

      return redirectionURL;
    }

    const orgCode = this.data.org?.custom_code ?? '';
    const orgID = this.data.org?.id ?? '';
    const countryCode = this.data?.user?.country_code ?? null;

    const isOrgRZP = orgCode === AppConstants.ORG_RZP;
    const isRZPOrgID = orgID === AppConstants.ORG_RZP_ID;
    const isFTUXApplicableForIndia = isRZPOrgID && countryCode === AppConstants.INDIA_COUNTRY_CODE;
    const isApplicableForFtuxRedirection =
      this.isRedirectionApplicableForFtux(this.data?.user || {}) && isOrgRZP;

    if (isApplicableForFtuxRedirection && isFTUXApplicableForIndia) {
      return process.env[AppConstants.EASY_DASHBOARD_URL] + '/onboarding/overview';
    }

    if (this.isPg3V1RedirectionApplicable(this.data?.user)) {
      return process.env[AppConstants.EASY_DASHBOARD_URL] + '/pg3/onboarding';
    }

    const signupCampaign = this.data?.user?.user?.signup_campaign ?? null;
    const submitted = this.data?.user?.submitted ?? null;
    const milestone = this.data?.user?.activation_form_milestone ?? null;
    const isPosSalesAgentRedirectApplicable = this.data.user?.role === AppConstants.RAZORPAY_SALES_ROLE;

    if (isPosSalesAgentRedirectApplicable) {
      const rzpSalesMid = this.data.user?.id ?? '';
      if (await this.switchToPosSalesAgent(this.data?.user)) {
        const queryParams = new URLSearchParams({
          source: AppConstants.SALES_ASSISTED_ONBOARDING_SOURCE,
          rzp_sales_mid: rzpSalesMid,
        }).toString();

        return `/app?${queryParams}`;
      }
    }

    if (signupCampaign === AppConstants.P2PM_ONBOARDING && submitted === 0 && milestone !== 'L2') {
      return process.env[AppConstants.EASY_DASHBOARD_URL] + '/onboarding/p2pm';
    }

    if (this.canCookieSetForEasyOnboardingPostL1Submit(this.data?.user || {})) {
      this.response.cookie(AppConstants.RZP_MERCHANT_ID, this.data?.user?.id, { maxAge: ttl });
      this.response.cookie(AppConstants.RZP_USER_ID, this.data?.user?.user?.id, { maxAge: ttl });
    }

    if (
      (this.data?.user?.user?.merchants?.length ?? 0) > 0 &&
      this.data.user_session?.disable_auto_merchant_login
    ) {
      return process.env[AppConstants.RAZORPAY_ACCOUNTS_URL] || '';
    }

    return null;
  }

  private isDomainRedirectionEnabled(id: string | null): boolean {
    if (!id) {
      // No experiment is set
      return true;
    }

    const experimentId = process.env[id] || '';

    const data = this.data?.user?.splitz_experiments[experimentId];

    if (!data) {
      return false;
    }

    return (data?.variables?.[0]?.value ?? null) === 'true';
  }

  private isPreSignupDetailsSetForNotRegisteredBusiness(): boolean {
    const input = this.data.preSignUpDetails;

    return AppConstants.NOT_REGISTERED_BUSINESS_PRE_SIGNUP_FIELDS.every(
      (key) =>
        key in input &&
        input[key as keyof typeof input] !== null &&
        input[key as keyof typeof input] !== undefined &&
        input[key as keyof typeof input] !== '',
    );
  }

  private isRedirectionApplicableToUnifiedLogin(queryParams: any): boolean {
    const existingRedirectionConditions = this.redirectionApplicableForGuest();

    if (queryParams[AppConstants.REFERRAL_CODE_KEY]) {
      return false;
    }

    // In case server and host is not there, fallback to default domain as we can't make a decision without its presence
    const domain = queryParams['host'] ?? AppConstants.DASHBOARD_PROD_HOSTNAME;

    const devServe = (this.request.headers[AppConstants.DEV_SERVE_USER_HEADER_KEY] as string) ?? '';

    // USL is only applicable for dashboard domain, do an exact match, in case of empty move forward
    const isDashboardDomain = this.getDashboardDomains(devServe).includes(domain);

    if (!isDashboardDomain) {
      return false;
    }

    if (!existingRedirectionConditions) {
      return false;
    }

    const unifiedExperimentID = process.env[AppConstants.UNIFIED_PG_REDIRECTION_ENABLED] || '';

    return this.data?.user?.splitz_experiments?.[unifiedExperimentID]?.variables?.result === 'on';
  }

  private redirectionApplicableForGuest(): boolean {
    if (this.isBankingOriginRequest()) {
      return false;
    }

    let uniqueid = this.request.cookies[AppConstants.RZP_AB_UUID_COOKIE_KEY] || uuid();
    this.response.cookie(AppConstants.RZP_AB_UUID_COOKIE_KEY, uniqueid);

    // EASY_ONBOARDING_REDIRECT as true.
    const referralExpId =
      process.env[AppConstants.PARTNERSHIPS_SUBMERCHANT_ONBOARDING_VIA_EASY] || '';

    const isReferralExpEnabled =
      this.data?.user?.splitz_experiments[referralExpId]?.variables?.result === 'on';

    if (!isReferralExpEnabled) {
      // TODO push metrics
      // this.metrics.count(MetricConstants.OLD_DASHBOARD_REDIRECT_COUNT, App.Http.Controllers.EVENT_TRIGGER_COUNT);
    }

    return !this.matchExclusionsToRedirect(isReferralExpEnabled);
  }

  private isBankingOriginRequest(shouldUseBankingOriginRequestV2 = false): boolean {
    const originHost = this.getRequestOriginHost();
    const requestHost = this.getRequestHost();

    if (shouldUseBankingOriginRequestV2) {
      const bankingUrls = (process.env[AppConstants.BANKING_SERVICE_URL_V2_KEY] || '').split(',');
      return bankingUrls.some(
        (url) =>
          this.getHostFromUrl(url.trim()) === originHost ||
          this.getHostFromUrl(url.trim()) === requestHost,
      );
    }

    const bankingHost = this.getHostFromUrl(
      process.env[AppConstants.BANKING_SERVICE_URL_KEY] || '',
    );
    const bankLmsBankingHost = this.getHostFromUrl(
      process.env[AppConstants.BANK_LMS_BANKING_SERVICE_URL_KEY] || '',
    );

    return originHost === bankingHost || originHost === bankLmsBankingHost;
  }

  private getRequestHost(): string {
    return this.getHostFromUrl(this.request.url, 'unknown_host');
  }

  private getHostFromUrl(urlvalue: string, defaultValue = 'unknown'): string {
    try {
      return new URL(urlvalue).hostname || defaultValue;
    } catch {
      return defaultValue;
    }
  }

  private getRequestOriginHost(): string {
    const source = this.getRequestOriginUrl();
    return this.getHostFromUrl(source, 'unknown_origin');
  }

  private getRequestOriginUrl(): string {
    // Since the client is loading the app in an iframe, we may not get the request with the correct product.
    // The client is sending an extra header for this purpose.
    let originDomain = this.request.headers['x-origin-product'] as string | undefined;

    if (!originDomain) {
      // Fallback for origin is referrer.
      // OWASP suggests using referrer if the origin header is not present.
      // https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet#Identifying_Source_Origin_.28via_Origin.2FReferer_header.29
      originDomain =
        (this.request.headers['origin'] as string) || (this.request.headers['referer'] as string);
    }

    return originDomain || '';
  }

  private matchExclusionsToRedirect(isExpEnabled: boolean = false): boolean {
    const uri = this.request.originalUrl.replace(/^\/|\/$/g, ''); // Trim leading and trailing slashes

    let pattern =
      /\b(r=partner|auth_source|referral_code|coupon_code|merchant_invitation|invitation)\b/;

    if (isExpEnabled) {
      pattern = /\b(r=partner|auth_source|coupon_code|merchant_invitation|invitation)\b/;
    }

    return pattern.test(uri);
  }

  private getDashboardDomains(devServe: string): Array<string> {
    return [
      AppConstants.DASHBOARD_PREFIX + devServe + AppConstants.DASHBOARD_SUFFIX_DEV,
      AppConstants.DASHBOARD_PREFIX + devServe + AppConstants.DASHBOARD_SUFFIX_INT_DEV,
      AppConstants.DASHBOARD_DEV,
      AppConstants.DASHBOARD_INT_DEV,
      AppConstants.DASHBOARD_PROD,
      AppConstants.CURLEC_PROD,
      AppConstants.CURLEC_COM,
    ];
  }

  private getUUID(): string {
    return this.request.cookies[AppConstants.AB_USER_COOKIE_KEY] || uuid();
  }

  private appendAllQueryParams(queryParams: Record<string, string>, redirectPath: string): string {
    try {
      const queryString = new URLSearchParams(queryParams).toString();
      return `${redirectPath}&${queryString}`;
    } catch (error) {
      // TODO check how to throw error
      /**
       * throw new BadRequestError(
       *             'Invalid query params',
       *             'BAD_REQUEST_ERROR',
       *             400
       *         );
       */
      throw new Error('Error while appending query params');
    }
  }

  private isRedirectionApplicable(): boolean {
    const isAdminAsMerchant = this.data.user_session.admin_logged_in_as_merchant;

    const shouldUseBankingOriginRequestV2 = this.isRedirectionExptEnabled();

    if (isAdminAsMerchant || this.isBankingOriginRequest(shouldUseBankingOriginRequestV2)) {
      return false;
    }

    if (this.isAuthSourceWebsite()) {
      return false;
    }

    const signupCampaign = this.data?.user?.user?.signup_campaign ?? null;
    const submitted = this.data?.user?.submitted ?? null;
    const activationStatus = this.data?.user?.activation_status ?? null;

    if (
      signupCampaign === AppConstants.EASY_ONBOARDING &&
      activationStatus === AppConstants.EDD_PENDING
    ) {
      return true;
    }

    return (
      (signupCampaign === AppConstants.I18N_MY_SIGNUP ||
        signupCampaign === AppConstants.EASY_ONBOARDING ||
        signupCampaign === AppConstants.SG_SIGNUP) &&
      !this.data?.user?.activation_form_milestone &&
      submitted === 0
    );
  }

  private isRedirectionExptEnabled(): boolean {
    const experimentId = process.env[AppConstants.DISABLE_EASY_REDIRECTION_FOR_BANKING] || '';

    return this.data?.user?.splitz_experiments[experimentId]?.variables?.[0]?.value === 'on';
  }

  private isAuthSourceWebsite(): boolean {
    const queryParams = this.request.query;
    const authSource = queryParams.auth_source as string | undefined;

    return (
      authSource === AppConstants.AUTH_SOURCE_WEBSITE ||
      authSource === AppConstants.AUTH_SOURCE_WEBSITE_HOMEPAGE
    );
  }

  private isRedirectionApplicableForFtux(details: any): boolean {
    try {
      const isAdminAsMerchant = this.data.user_session.is_admin_logged_in_as_merchant;
      const isSubMerchant = details.isSubMerchant ?? false;
      const partnerType = details.partner_type ?? null;
      const countryCode = details.country_code ?? null;
      const shouldUseBankingOriginRequestV2 = this.isRedirectionExptEnabled();

      if (
        isAdminAsMerchant ||
        isSubMerchant ||
        partnerType ||
        this.isEligibleForPos(details) ||
        this.isBankingOriginRequest(shouldUseBankingOriginRequestV2)
      ) {
        return false;
      }

      const workflowType = details?.workflow_type;
      const workflowDetails = details?.workflow_details || {};

      if (
        (workflowType && workflowType === AppConstants.MODULAR_ONBOARDING) ||
        workflowDetails.pg_onboarding_workflow_type === AppConstants.MODULAR_ONBOARDING
      ) {
        return false;
      }

      const signupCampaign = details?.user?.signup_campaign ?? null;

      if (signupCampaign !== AppConstants.EASY_ONBOARDING) {
        return false;
      }

      // Do not show FTUX if country is not India
      if (
        signupCampaign === AppConstants.EASY_ONBOARDING &&
        countryCode !== AppConstants.INDIA_COUNTRY_CODE
      ) {
        return false;
      }

      if (!this.isFtuxExperimentEnabled()) {
        return false;
      }

      if (typeof this.request.cookies[AppConstants.FTUX_SESSION] !== 'undefined') {
        return false;
      }

      if (
        this.isFtuxAfterL2ExperimentEnabled() &&
        details[AppConstants.ACTIVATION_STATUS] === null
      ) {
        return false;
      }

      if (
        details?.activation_status !== AppConstants.ACTIVATION_STATUS_ACTIVATED &&
        details?.activation_status !== AppConstants.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING
      ) {
        return true;
      }

      if (details?.isTransacted === false) {
        return true;
      }

      if (this.data.config_store?.show_ftux_final_screen) {
        return true;
      }

      return false;
    } catch (error) {
      return false;
    }
  }

  private isEligibleForPos(details: any): boolean {
    const physicalStore =
      details?.[AppConstants.MERCHANT_BUSINESS_DETAIL]?.[AppConstants.WEBSITE_DETAILS]?.[
        AppConstants.PHYSICAL_STORE
      ] ?? false;

    if (physicalStore !== true) {
      return false;
    }

    const experimentId = process.env[AppConstants.ELIGIBLE_FOR_POS] || '';

    return (
      this.data?.user?.splitz_experiments?.[experimentId]?.[AppConstants.VARIABLES]?.[
        AppConstants.RESULT
      ] === 'on'
    );
  }

  private isFtuxExperimentEnabled(): boolean {
    const experimentId = process.env[AppConstants.ONBOARDING_FTUX] || '';
    return (
      this.data?.user?.splitz_experiments?.[experimentId]?.[AppConstants.VARIABLES]?.[
        AppConstants.RESULT
      ] === 'on'
    );
  }

  private isFtuxAfterL2ExperimentEnabled(): boolean {
    const experimentId = process.env[AppConstants.ONBOARDING_FTUX_AFTER_L2] || '';
    return (
      this.data?.user?.splitz_experiments?.[experimentId]?.[AppConstants.VARIABLES]?.[
        AppConstants.RESULT
      ] === 'on'
    );
  }

  private isPg3V1RedirectionApplicable(details: any): boolean {
    if (details.activation_status !== AppConstants.ACTIVATION_STATUS_ACTIVATED) {
      return false;
    }

    const experimentID = process.env[AppConstants.PG3_V1_ENABLED] || '';

    const data = this.data?.user?.splitz_experiments || {};

    if (data[experimentID]?.name === 'enabled') {
      const merchantFeatures = this.data?.user?.features || [];

      if (
        merchantFeatures.includes(AppConstants.SHOW_PG_V3) &&
        !merchantFeatures.includes(AppConstants.PG_V3_ONBOARDING_COMPLETE)
      ) {
        return true;
      }
    }

    return false;
  }

  private async switchToPosSalesAgent(details: any): Promise<boolean> {
    try {
      // Get the first merchant with the role of partner agent
        const partnerAgentMerchant = Object.values(details.merchants || {}).find(
            (merchant: any) => merchant.role === AppConstants.PARTNER_AGENT_ROLE,
        );

      if (partnerAgentMerchant?.id) {
        await switchCurrentMerchant(
          // @ts-ignore
          this.request,
          this.response,
          partnerAgentMerchant.id,
        );

        return true;
      }
    } catch (error) {
      this.request.shellLogger.warn({
        moduleName: '@shellRedirectionService',
        message: 'PARTNER_AGENT_SWITCH_FAILED',
        context: {
          error: (error as Error).message,
        },
      });
    }

    return false;
  }

  private canCookieSetForEasyOnboardingPostL1Submit(details: any): boolean {
    if (this.isAuthSourceWebsite()) {
      return false;
    }

    const signupCampaign = details?.user?.signup_campaign;
    const activationFormMilestone = details?.activation_form_milestone;
    const submitted = details?.submitted;
    const activationStatus = details?.activation_status;
    const isActivationFormMilestoneReached = [
      AppConstants.ACTIVATION_FORM_MILESTONE_L1,
      AppConstants.ACTIVATION_FORM_MILESTONE_L2,
    ].includes(activationFormMilestone);

    if (signupCampaign === AppConstants.EASY_ONBOARDING && isActivationFormMilestoneReached) {
      return true;
    }

    if (
      (isActivationFormMilestoneReached || submitted === 1) &&
      activationStatus !== AppConstants.ACTIVATION_STATUS_ACTIVATED
    ) {
      return true;
    }

    return false;
  }
}
