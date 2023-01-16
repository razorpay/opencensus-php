const mockRazorXExp = {
  isInstantActivationEnabled: true,
  canSkipPoiValidation: false,
  canGenerateTnCPage: true,
  isBDAndAovEnabled: true,
  isAadharEkycMandatory: true,
  isSyncBankVerificationEnabled: true,
  isEmailMandatoryOnL1: true,
  isEmailNonMandatoryOnL1: false,
  isEmailNonMandatoryOnL2Form: false,
  isActivationFormFullView: true,
  isGstinSyncFlowEnabled: true,
  isLlpinSyncFlowEnabled: true,
  isCinSyncFlowEnabled: true,
  isActivationMccPendingProgressbarDisabled: true,
  isMsmeDisabled: true,
  isAdharEkycRequiredForTrustSocietyNgo: true,
  isFeEasyDashboardNCEnabled: true,
};

const mockContext = {
  mode: 'test',
  org: { id: '123' },
  user: { contact_name: 'prashant' },
  experiments: mockRazorXExp,
};

const COMPONENT_WRAPPER_TESTID = 'component-wrapper';

export { mockContext, COMPONENT_WRAPPER_TESTID };
