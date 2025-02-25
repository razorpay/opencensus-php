import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLRequireFields, DashboardGraphQLMutationAadhaarCaptchaVerifyArgs, DashboardGraphQLMutationAadhaarDigilockerOtpArgs, DashboardGraphQLMutationAadhaarDigilockerRedirectionUrlArgs, DashboardGraphQLMutationAadhaarDigilockerRedirectionUrlVerifyArgs, DashboardGraphQLMutationAadhaarOtpVerifyArgs, DashboardGraphQLMutationAadharDigilockerOtpVerifyArgs, DashboardGraphQLMutationAccountVerificationOtpArgs, DashboardGraphQLMutationAccountVerificationOtpResendArgs, DashboardGraphQLMutationAccountVerifyArgs, DashboardGraphQLMutationApproveIciciPayoutArgs, DashboardGraphQLMutationApprovePayoutArgs, DashboardGraphQLMutationApprovePayoutBatchArgs, DashboardGraphQLMutationCouponValidateArgs, DashboardGraphQLMutationCreateWithdrawalArgs, DashboardGraphQLMutationDeregisterFcmTokenArgs, DashboardGraphQLMutationLoginEmailArgs, DashboardGraphQLMutationLoginEmailVerifyArgs, DashboardGraphQLMutationLoginOAuthArgs, DashboardGraphQLMutationLoginOtpArgs, DashboardGraphQLMutationLoginOtpResendArgs, DashboardGraphQLMutationLoginOtpVerifyArgs, DashboardGraphQLMutationLoginTwoFactorArgs, DashboardGraphQLMutationLoginTwoFactorPasswordArgs, DashboardGraphQLMutationMerchantActivationDetailsUpdateArgs, DashboardGraphQLMutationMerchantActivationDocumentDeleteArgs, DashboardGraphQLMutationMerchantActivationDocumentUploadArgs, DashboardGraphQLMutationMerchantApiKeyRegenerateArgs, DashboardGraphQLMutationMerchantBankAccountDocumentUploadArgs, DashboardGraphQLMutationMerchantBankAccountUpdateArgs, DashboardGraphQLMutationMerchantBusinessAppDetailsUpdateArgs, DashboardGraphQLMutationMerchantBusinessWebsiteDetailsUpdateArgs, DashboardGraphQLMutationMerchantClarificationDetailsSubmitArgs, DashboardGraphQLMutationMerchantClarificationDetailsUpdateArgs, DashboardGraphQLMutationMerchantConfigUpdateArgs, DashboardGraphQLMutationMerchantConfigurationUpdateArgs, DashboardGraphQLMutationMerchantContactCreateArgs, DashboardGraphQLMutationMerchantContactEmailOtpSendArgs, DashboardGraphQLMutationMerchantContactFundAccountCreateArgs, DashboardGraphQLMutationMerchantContactTypeCreateArgs, DashboardGraphQLMutationMerchantContactUpdateArgs, DashboardGraphQLMutationMerchantCreateVkycLinkArgs, DashboardGraphQLMutationMerchantDocumentUploadArgs, DashboardGraphQLMutationMerchantEmailUpdateArgs, DashboardGraphQLMutationMerchantGstinUpdateArgs, DashboardGraphQLMutationMerchantGstinUpdateV2Args, DashboardGraphQLMutationMerchantInstrumentCancelRequestArgs, DashboardGraphQLMutationMerchantInstrumentCreateRequestArgs, DashboardGraphQLMutationMerchantInstrumentReInitiateRequestArgs, DashboardGraphQLMutationMerchantKycPartnerAccessUpdateArgs, DashboardGraphQLMaybe, DashboardGraphQLMutationMerchantModularOnboardingDetailsUpdateArgs, DashboardGraphQLMutationMerchantOnboardingQuestionDetailsUpdateArgs, DashboardGraphQLMutationMerchantPaymentHandleEncryptedAmountArgs, DashboardGraphQLMutationMerchantPaymentHandleUpdateArgs, DashboardGraphQLMutationMerchantPolicyPublishArgs, DashboardGraphQLMutationMerchantSendMobileOtpArgs, DashboardGraphQLMutationMerchantStoreActivateArgs, DashboardGraphQLMutationMerchantStoreConsentsArgs, DashboardGraphQLMutationMerchantStoreDeactivateArgs, DashboardGraphQLMutationMerchantStoreUpdateArgs, DashboardGraphQLMutationMerchantSwitchArgs, DashboardGraphQLMutationMerchantSwitchOAuthArgs, DashboardGraphQLMutationMerchantVerifyMobileOtpArgs, DashboardGraphQLMutationMerchantWebsiteDetailsUpdateArgs, DashboardGraphQLMutationMerchantWebsiteDocumentDeleteArgs, DashboardGraphQLMutationMerchantWebsiteDocumentUploadArgs, DashboardGraphQLMutationMerchantWebsitePublishArgs, DashboardGraphQLMutationMerchantWebsitesVerificationArgs, DashboardGraphQLMutationMerchantWorkflowClarificationSubmitArgs, DashboardGraphQLMutationNotificationEmailUpdateArgs, DashboardGraphQLMutationNotificationWhatsAppOptInArgs, DashboardGraphQLMutationOAuthTokenAppleWatchArgs, DashboardGraphQLMutationOnboardingPaymentOrderCreateArgs, DashboardGraphQLMutationOnboardingPaymentOrderVerifyArgs, DashboardGraphQLMutationOptInForWhatsappArgs, DashboardGraphQLMutationOrderCreateArgs, DashboardGraphQLMutationPaymentCaptureArgs, DashboardGraphQLMutationPaymentLinkCancelArgs, DashboardGraphQLMutationPaymentLinkCreateArgs, DashboardGraphQLMutationPaymentLinkNotifyArgs, DashboardGraphQLMutationPaymentRefundArgs, DashboardGraphQLMutationPaymentsProductFtuxUpdateArgs, DashboardGraphQLMutationPayoutApproveBulkArgs, DashboardGraphQLMutationPayoutCompositeCreateArgs, DashboardGraphQLMutationPayoutCreateArgs, DashboardGraphQLMutationPayoutCreateIciciArgs, DashboardGraphQLMutationPayoutLinkCreateArgs, DashboardGraphQLMutationPayoutPurposeCreateArgs, DashboardGraphQLMutationPayoutRejectBulkArgs, DashboardGraphQLMutationPettyCashCreateArgs, DashboardGraphQLMutationPointOfSalePaymentCreateArgs, DashboardGraphQLMutationPointOfSalePaymentUpdateArgs, DashboardGraphQLMutationQrCodeCreateArgs, DashboardGraphQLMutationRefreshAccessTokenArgs, DashboardGraphQLMutationRegisterBusinessArgs, DashboardGraphQLMutationRegisterEmailArgs, DashboardGraphQLMutationRegisterEmailVerifyArgs, DashboardGraphQLMutationRegisterFcmTokenArgs, DashboardGraphQLMutationRegisterMerchantArgs, DashboardGraphQLMutationRegisterMobileVerifyArgs, DashboardGraphQLMutationRegisterOAuthArgs, DashboardGraphQLMutationRejectPayoutArgs, DashboardGraphQLMutationRejectPayoutBatchArgs, DashboardGraphQLMutationResendEmailOtpArgs, DashboardGraphQLMutationResendTwoFactorLoginOtpArgs, DashboardGraphQLMutationResetPasswordEmailArgs, DashboardGraphQLMutationSendApprovePayoutBatchOtpArgs, DashboardGraphQLMutationSendApprovePayoutOtpArgs, DashboardGraphQLMutationSendCreatePayoutLinkOtpArgs, DashboardGraphQLMutationSendCreatePayoutOtpArgs, DashboardGraphQLMutationSendEmailVerificationOtpArgs, DashboardGraphQLMutationSendIciciPayoutOtpArgs, DashboardGraphQLMutationSendPayoutApproveBulkOtpArgs, DashboardGraphQLMutationSendPayoutCompositeOtpArgs, DashboardGraphQLMutationSetEmailPasswordArgs, DashboardGraphQLMutationSetNewPasswordArgs, DashboardGraphQLMutationSmsNotificationToggleArgs, DashboardGraphQLMutationTwoFactorAddMobileOtpArgs, DashboardGraphQLMutationTwoFactorAddMobileOtpVerifyArgs, DashboardGraphQLMutationTwoFactorAuthUpdateArgs, DashboardGraphQLMutationTwoFactorEmailOtpVerifyArgs, DashboardGraphQLMutationTwoFactorOtpArgs, DashboardGraphQLMutationTwoFactorPasswordCreateArgs, DashboardGraphQLMutationTwoFactorUnverifiedMobileVerifyArgs, DashboardGraphQLMutationUpdateMerchantConsentArgs, DashboardGraphQLMutationUserContactDetailsUpdateArgs, DashboardGraphQLMutationUserDeviceAnalyticsUpdateArgs, DashboardGraphQLMutationUserExistsByEmailOrPhoneArgs, DashboardGraphQLMutationUserLogoutOAuthArgs, DashboardGraphQLMutationUserOtpVerifyArgs, DashboardGraphQLMutationVendorPaymentCancelArgs, DashboardGraphQLMutationVendorPaymentPayoutCreateArgs, DashboardGraphQLMutationVerifyEmailOtpArgs, DashboardGraphQLMutationWhatsappNotificationToggleArgs } from './index';
export type DashboardGraphQLMutationResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMutation'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMutation'],
> = {
  aadhaarCaptchaVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarCaptchaVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAadhaarCaptchaVerifyArgs, 'aadhaarNumber' | 'captcha'>
  >;
  aadhaarDigilockerOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarDigilockerOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAadhaarDigilockerOtpArgs, 'aadhaarNumber'>
  >;
  aadhaarDigilockerRedirectionUrl?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarDigilockerRedirectionUrlResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAadhaarDigilockerRedirectionUrlArgs, 'redirectUrl' | 'verificationType'>
  >;
  aadhaarDigilockerRedirectionUrlVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarDigilockerRedirectionUrlVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAadhaarDigilockerRedirectionUrlVerifyArgs, 'verificationType'>
  >;
  aadhaarOtpVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarOtpVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAadhaarOtpVerifyArgs, 'captcha' | 'filePassword' | 'otp'>
  >;
  aadharDigilockerOtpVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarDigilockerOtpVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAadharDigilockerOtpVerifyArgs, 'aadhaarNumber' | 'otp' | 'requestId'>
  >;
  accountVerificationOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAccountVerificationOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAccountVerificationOtpArgs, 'password'>
  >;
  accountVerificationOtpResend?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAccountVerificationOtpResendResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAccountVerificationOtpResendArgs, 'password' | 'token'>
  >;
  accountVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationAccountVerifyArgs, 'otp' | 'token'>
  >;
  approveIciciPayout?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLApproveIciciPayoutResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationApproveIciciPayoutArgs, 'id' | 'otp'>
  >;
  approvePayout?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLApprovePayoutResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationApprovePayoutArgs, 'id' | 'otp' | 'queueOnLowBalance' | 'token'>
  >;
  approvePayoutBatch?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLApprovePayoutBatchResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationApprovePayoutBatchArgs, 'batchIds' | 'otp' | 'token'>
  >;
  couponApply?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLCouponApplyResponse'], ParentType, ContextType>;
  couponValidate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLCouponValidateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationCouponValidateArgs, 'code'>
  >;
  createWithdrawal?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['createWithdrawalResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationCreateWithdrawalArgs,
      | 'amount'
      | 'drawn_at'
      | 'due_date'
      | 'message'
      | 'owner_id'
      | 'owner_type'
      | 'start_date'
      | 'tenure'
      | 'withdrawal_config_id'
    >
  >;
  deregisterFCMToken?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DeregisterFCMTokenResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationDeregisterFcmTokenArgs, 'productType' | 'tokenIdentifier'>
  >;
  loginEmail?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginEmailArgs, 'email' | 'password'>
  >;
  loginEmailVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginEmailVerifyArgs, 'otp' | 'token'>
  >;
  loginOAuth?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginOAuthArgs, 'email' | 'idToken' | 'platform' | 'provider'>
  >;
  loginOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLLoginOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginOtpArgs, 'phone'>
  >;
  loginOtpResend?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLLoginOtpResendResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginOtpResendArgs, 'phone'>
  >;
  loginOtpVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginOtpVerifyArgs, 'otp' | 'phone' | 'token'>
  >;
  loginTwoFactor?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginTwoFactorArgs, 'code'>
  >;
  loginTwoFactorPassword?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationLoginTwoFactorPasswordArgs, 'password'>
  >;
  merchantActivationDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLMutationMerchantActivationDetailsUpdateArgs>
  >;
  merchantActivationDocumentDelete?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantActivationDocumentDeleteArgs, 'documentId'>
  >;
  merchantActivationDocumentUpload?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantActivationDocumentUploadArgs, 'file' | 'name'>
  >;
  merchantApiKeyCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantApiKeyCreateResponse'],
    ParentType,
    ContextType
  >;
  merchantApiKeyRegenerate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantApiKeyRegenerateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantApiKeyRegenerateArgs, 'apiKeyRegenerationDelayType' | 'oldApiKey'>
  >;
  merchantApiKeysCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantApiKeysCreateResponse'],
    ParentType,
    ContextType
  >;
  merchantBankAccountDocumentUpload?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankAccountDocumentUploadResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantBankAccountDocumentUploadArgs, 'document'>
  >;
  merchantBankAccountUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankAccountUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationMerchantBankAccountUpdateArgs,
      'accountNumber' | 'beneficiaryName' | 'ifscCode'
    >
  >;
  merchantBusinessAppDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessAppDetailsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantBusinessAppDetailsUpdateArgs, 'businessApp'>
  >;
  merchantBusinessWebsiteDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessWebsiteDetailsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantBusinessWebsiteDetailsUpdateArgs, 'businessWebsite'>
  >;
  merchantClarificationDetailsSubmit?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantClarificationDetailsSubmitResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLMutationMerchantClarificationDetailsSubmitArgs>
  >;
  merchantClarificationDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantClarificationDetailsUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantClarificationDetailsUpdateArgs, 'fieldName'>
  >;
  merchantConfigUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantConfigUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantConfigUpdateArgs, 'namespace'>
  >;
  merchantConfigurationUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['merchantConfigurationUpdateResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLMutationMerchantConfigurationUpdateArgs>
  >;
  merchantContactCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantContactCreateArgs, 'name'>
  >;
  merchantContactEmailOtpSend?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactEmailOtpSendResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantContactEmailOtpSendArgs, 'email'>
  >;
  merchantContactFundAccountCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactFundAccountCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantContactFundAccountCreateArgs, 'contactId' | 'type'>
  >;
  merchantContactTypeCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactTypeCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantContactTypeCreateArgs, 'type'>
  >;
  merchantContactUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantContactUpdateArgs, 'id'>
  >;
  merchantCreateVkycLink?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantCreateVkycLinkResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantCreateVkycLinkArgs, 'name'>
  >;
  merchantDocumentUpload?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantDocumentUploadResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantDocumentUploadArgs, 'document' | 'purpose'>
  >;
  merchantEmailUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantEmailUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantEmailUpdateArgs, 'shouldUpdateContactEmail' | 'updatedEmail'>
  >;
  merchantGstinUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantGstinUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantGstinUpdateArgs, 'gstin' | 'gstinCertificate'>
  >;
  merchantGstinUpdateV2?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantGstinUpdateV2Response'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantGstinUpdateV2Args, 'gstin'>
  >;
  merchantInstrumentCancelRequest?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantInstrumentCancelRequestMutationResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantInstrumentCancelRequestArgs, 'instrumentRequestId'>
  >;
  merchantInstrumentCreateRequest?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantInstrumentCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantInstrumentCreateRequestArgs, 'instrument'>
  >;
  merchantInstrumentReInitiateRequest?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantInstrumentReInitiateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantInstrumentReInitiateRequestArgs, 'instrumentRequestId'>
  >;
  merchantKYCPartnerAccessUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['MerchantKYCPartnerAccessStatusUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantKycPartnerAccessUpdateArgs, 'referralCode' | 'status'>
  >;
  merchantModularOnboardingDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['merchantModularOnboardingDetailsUpdateResponse']>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantModularOnboardingDetailsUpdateArgs, 'merchantId'>
  >;
  merchantOnboardingQuestionDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantOnboardingQuestionDetailsUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantOnboardingQuestionDetailsUpdateArgs, 'questionDetails'>
  >;
  merchantPaymentHandleCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPaymentHandleCreateResponse'],
    ParentType,
    ContextType
  >;
  merchantPaymentHandleEncryptedAmount?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPaymentHandleEncryptedAmountResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantPaymentHandleEncryptedAmountArgs, 'amount'>
  >;
  merchantPaymentHandleUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPaymentHandleUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantPaymentHandleUpdateArgs, 'paymentHandleSlug'>
  >;
  merchantPolicyPublish?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPolicyPublishResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantPolicyPublishArgs, 'action' | 'section'>
  >;
  merchantSendMobileOTP?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['MerchantSendMobileOTPResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantSendMobileOtpArgs, 'contact'>
  >;
  merchantStoreActivate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStoreActivateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantStoreActivateArgs, 'id'>
  >;
  merchantStoreConsents?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantConsentsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantStoreConsentsArgs, 'consents' | 'event'>
  >;
  merchantStoreDeactivate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStoreDeactivateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantStoreDeactivateArgs, 'id'>
  >;
  merchantStoreUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStoreUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantStoreUpdateArgs, 'id'>
  >;
  merchantSwitch?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantSwitchResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantSwitchArgs, 'id'>
  >;
  merchantSwitchOAuth?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantSwitchResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantSwitchOAuthArgs, 'accessToken' | 'clientId' | 'id'>
  >;
  merchantVerifyMobileOTP?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['MerchantVerifyMobileOTPResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantVerifyMobileOtpArgs, 'contact' | 'otp'>
  >;
  merchantWebsiteDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsite'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLMutationMerchantWebsiteDetailsUpdateArgs>
  >;
  merchantWebsiteDocumentDelete?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsiteDocumentDeleteResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantWebsiteDocumentDeleteArgs, 'platform' | 'section'>
  >;
  merchantWebsiteDocumentUpload?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsiteDocumentUploadResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationMerchantWebsiteDocumentUploadArgs,
      'platform' | 'section' | 'websiteDocument'
    >
  >;
  merchantWebsitePublish?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsitePublishResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantWebsitePublishArgs, 'action' | 'hasMerchantConsent' | 'section'>
  >;
  merchantWebsitesVerification?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsiteVerificationResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationMerchantWebsitesVerificationArgs, 'websiteLinks'>
  >;
  merchantWorkflowClarificationSubmit?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWorkflowClarificationSubmitResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationMerchantWorkflowClarificationSubmitArgs,
      'clarificationReason' | 'documentIds' | 'workflow'
    >
  >;
  notificationEmailUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLNotificationEmailUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationNotificationEmailUpdateArgs, 'transactionReportEmail'>
  >;
  notificationWhatsAppOptIn?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLNotificationWhatsAppOptIn'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationNotificationWhatsAppOptInArgs, 'source'>
  >;
  oAuthTokenAppleWatch?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOauthTokenAppleWatchResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationOAuthTokenAppleWatchArgs, 'otp' | 'token'>
  >;
  oAuthTokenAppleWatchOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOauthTokenAppleWatchOtp'],
    ParentType,
    ContextType
  >;
  onboardingPaymentOrderCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOnboardingPaymentOrderCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationOnboardingPaymentOrderCreateArgs, 'createOrder'>
  >;
  onboardingPaymentOrderVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOnboardingPaymentOrderVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationOnboardingPaymentOrderVerifyArgs, 'orderId' | 'paymentId' | 'signature'>
  >;
  optInForWhatsapp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOptInForWhatsappResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationOptInForWhatsappArgs, 'business_account' | 'source'>
  >;
  orderCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOrderCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationOrderCreateArgs, 'amount'>
  >;
  paymentCapture?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentCaptureResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPaymentCaptureArgs, 'amount' | 'id'>
  >;
  paymentLinkCancel?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkCancelResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPaymentLinkCancelArgs, 'id'>
  >;
  paymentLinkCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPaymentLinkCreateArgs, 'amount'>
  >;
  paymentLinkNotify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkNotifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPaymentLinkNotifyArgs, 'id' | 'medium'>
  >;
  paymentRefund?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentRefundResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPaymentRefundArgs, 'amount' | 'id'>
  >;
  paymentsNewLaunchProductViewUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsNewLaunchProductViewUpdate'],
    ParentType,
    ContextType
  >;
  paymentsProductFtuxUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsProductFtuxUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPaymentsProductFtuxUpdateArgs, 'isFtuxComplete' | 'product'>
  >;
  payoutApproveBulk?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutApproveBulkResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPayoutApproveBulkArgs, 'ids' | 'otp' | 'queueOnLowBalance' | 'token'>
  >;
  payoutCompositeCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutCompositeCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationPayoutCompositeCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'fundAccountType'
      | 'merchantContact'
      | 'mode'
      | 'otp'
      | 'purpose'
      | 'queueOnLowBalance'
      | 'token'
      | 'vpa'
    >
  >;
  payoutCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationPayoutCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'fundAccountId'
      | 'mode'
      | 'otp'
      | 'purpose'
      | 'queueOnLowBalance'
      | 'token'
    >
  >;
  payoutCreateIcici?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutCreateIciciResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationPayoutCreateIciciArgs,
      'amount' | 'bankingAccountNumber' | 'fundAccountId' | 'mode' | 'purpose' | 'queueOnLowBalance'
    >
  >;
  payoutLinkCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutLinkCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationPayoutLinkCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'description'
      | 'merchantContactId'
      | 'otp'
      | 'purpose'
      | 'sendVia'
      | 'token'
    >
  >;
  payoutPurposeCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutPurposeCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPayoutPurposeCreateArgs, 'label' | 'type'>
  >;
  payoutRejectBulk?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutRejectBulkResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPayoutRejectBulkArgs, 'ids'>
  >;
  pettyCashCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPettyCashCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationPettyCashCreateArgs,
      | 'amount'
      | 'budgetId'
      | 'destinationAccountDetails'
      | 'expenseCategoryId'
      | 'mode'
      | 'otp'
      | 'purpose'
      | 'queueOnLowBalance'
      | 'token'
    >
  >;
  pointOfSalePaymentCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPointOfSalePaymentCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationPointOfSalePaymentCreateArgs,
      'amount' | 'application' | 'method' | 'orderId'
    >
  >;
  pointOfSalePaymentUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPointOfSalePaymentUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationPointOfSalePaymentUpdateArgs, 'amount' | 'transaction' | 'url'>
  >;
  qrCodeCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['QRCodeCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationQrCodeCreateArgs, 'type' | 'usage'>
  >;
  refreshAccessToken?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRefreshAccessToken'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRefreshAccessTokenArgs, 'clientId' | 'merchantId' | 'refreshToken'>
  >;
  registerBusiness?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRegisterBusinessResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRegisterBusinessArgs, 'name'>
  >;
  registerEmail?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRegisterEmail'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationRegisterEmailArgs,
      'confirmPassword' | 'email' | 'password' | 'verificationMethod'
    >
  >;
  registerEmailVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRegisterEmailVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRegisterEmailVerifyArgs, 'otp' | 'token'>
  >;
  registerFCMToken?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['RegisterFCMTokenResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationRegisterFcmTokenArgs,
      'fcmToken' | 'platform' | 'productType' | 'tokenIdentifier'
    >
  >;
  registerMerchant?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRegisterMerchantResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRegisterMerchantArgs, 'contact'>
  >;
  registerMobileVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRegisterMobileVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRegisterMobileVerifyArgs, 'captcha' | 'contact' | 'otp' | 'token'>
  >;
  registerOAuth?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRegisterOAuth'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRegisterOAuthArgs, 'email' | 'idToken' | 'platform' | 'provider'>
  >;
  rejectPayout?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRejectPayoutResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRejectPayoutArgs, 'id'>
  >;
  rejectPayoutBatch?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRejectPayoutBatchResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationRejectPayoutBatchArgs, 'batchIds'>
  >;
  resendEmailOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLResendEmailOtp'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationResendEmailOtpArgs, 'token'>
  >;
  resendTwoFactorLoginOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLResendTwoFactorLoginOtpResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLMutationResendTwoFactorLoginOtpArgs>
  >;
  resetPasswordEmail?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLResetPasswordEmail'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationResetPasswordEmailArgs, 'email'>
  >;
  sendApprovePayoutBatchOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendApprovePayoutBatchOtp'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationSendApprovePayoutBatchOtpArgs,
      'bankingAccountNumber' | 'totalAmount' | 'totalCount'
    >
  >;
  sendApprovePayoutOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendApprovePayoutOtp'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationSendApprovePayoutOtpArgs, 'amount' | 'bankingAccountNumber' | 'payoutId'>
  >;
  sendCreatePayoutLinkOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendCreatePayoutLinkOtp'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationSendCreatePayoutLinkOtpArgs,
      'amount' | 'bankingAccountNumber' | 'purpose'
    >
  >;
  sendCreatePayoutOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendCreatePayoutOtp'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationSendCreatePayoutOtpArgs,
      'amount' | 'bankingAccountNumber' | 'fundAccountId' | 'purpose'
    >
  >;
  sendEmailVerificationOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendEmailVerificationOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationSendEmailVerificationOtpArgs, 'email'>
  >;
  sendIciciPayoutOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendIciciPayoutOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationSendIciciPayoutOtpArgs, 'payoutId'>
  >;
  sendPayoutApproveBulkOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendPayoutApproveBulkOtp'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationSendPayoutApproveBulkOtpArgs, 'amount' | 'bankingAccountNumber' | 'count'>
  >;
  sendPayoutCompositeOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSendPayoutCompositeOtp'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationSendPayoutCompositeOtpArgs, 'amount' | 'bankingAccountNumber' | 'vpa'>
  >;
  setEmailPassword?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSetEmailPasswordResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationSetEmailPasswordArgs, 'password' | 'password_confirmation'>
  >;
  setNewPassword?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSetNewPasswordResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationSetNewPasswordArgs,
      'email' | 'password' | 'password_confirmation' | 'token'
    >
  >;
  smsNotificationToggle?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSmsNotificationToggle'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationSmsNotificationToggleArgs, 'toggleValue'>
  >;
  twoFactorAddMobileOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorAddMobileOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationTwoFactorAddMobileOtpArgs, 'phone' | 'token'>
  >;
  twoFactorAddMobileOtpVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorAddMobileOtpVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationTwoFactorAddMobileOtpVerifyArgs, 'otp' | 'phone'>
  >;
  twoFactorAuthUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorAuthUpdateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationTwoFactorAuthUpdateArgs, 'isTwoFactorEnabled'>
  >;
  twoFactorEmailOtpVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorEmailOtpVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationTwoFactorEmailOtpVerifyArgs, 'action' | 'otp' | 'token'>
  >;
  twoFactorOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationTwoFactorOtpArgs, 'action' | 'medium'>
  >;
  twoFactorPasswordCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorPasswordCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationTwoFactorPasswordCreateArgs, 'confirmPassword' | 'password'>
  >;
  twoFactorUnverifiedMobileVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorUnverifiedMobileVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationTwoFactorUnverifiedMobileVerifyArgs, 'otp' | 'token'>
  >;
  updateMerchantConsent?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUpdateMerchantConsentResponse']>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationUpdateMerchantConsentArgs, 'partnerId'>
  >;
  userContactDetailsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRegisterBusinessResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationUserContactDetailsUpdateArgs, 'name'>
  >;
  userDeviceAnalyticsUpdate?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUserDeviceAnalyticsResponse']>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationUserDeviceAnalyticsUpdateArgs, 'analyticsData'>
  >;
  userExistsByEmailOrPhone?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLUserExistsByEmailOrPhoneResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLMutationUserExistsByEmailOrPhoneArgs>
  >;
  userLogout?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLUserLogout'], ParentType, ContextType>;
  userLogoutOAuth?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLUserLogout'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationUserLogoutOAuthArgs, 'accessToken' | 'clientId'>
  >;
  userOtp?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['userOtpResponse'], ParentType, ContextType>;
  userOtpVerify?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLUserOtpVerifyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationUserOtpVerifyArgs, 'otp'>
  >;
  vendorPaymentCancel?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLVendorPaymentCancelResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationVendorPaymentCancelArgs, 'id'>
  >;
  vendorPaymentPayoutCreate?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLVendorPaymentPayoutCreateResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLMutationVendorPaymentPayoutCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'fundAccountId'
      | 'mode'
      | 'otp'
      | 'purpose'
      | 'queueOnLowBalance'
      | 'token'
      | 'vendorPaymentId'
    >
  >;
  verifyEmailOtp?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLVerifyEmailOtpResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationVerifyEmailOtpArgs, 'email' | 'otp' | 'token'>
  >;
  whatsappNotificationToggle?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLWhatsappNotificationToggle'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLMutationWhatsappNotificationToggleArgs, 'source' | 'toggleValue'>
  >;
};