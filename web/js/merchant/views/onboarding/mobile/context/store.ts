import create from 'zustand';
import {
  isUnregisteredBusiness,
  showForOrgs,
  isBusinessProofTypeDocFieldVisible,
  doesHaveAdditionalDocs,
  isBusinessProofUrlVisible,
  isBusinessPanVisible,
  isPersonalPanVisible,
  isL1Submitted,
  canShowAadharDoc,
} from '../services/utils';
import { CIN_BusinessTypes, LLPIN_BusinessTypes } from '../Constants/OnboardingConstants';

const isVisible = (fieldName, context) => {
  switch (fieldName) {
    case 'company_pan':
      return !['11', '2', '1'].includes(context.business_overview.business_type.value);
    case 'business_name':
      return !isUnregisteredBusiness(context.business_overview.business_type.value);
    case 'business_website':
      return context.hasWebsite;
    case 'business_operation_address':
    case 'business_operation_state':
    case 'business_operation_city':
    case 'business_operation_pin':
      return (
        !isUnregisteredBusiness(context.business_overview.business_type.value) &&
        !context.sameAddress
      );
    case 'gstin':
      return (
        !isUnregisteredBusiness(context.business_overview.business_type.value) &&
        (isL1Submitted(context.activation_form_milestone) || !context.isInstantActivationEnabled)
      );
    case 'company_cin':
      return (
        CIN_BusinessTypes.includes(Number(context.business_overview.business_type.value)) ||
        LLPIN_BusinessTypes.includes(Number(context.business_overview.business_type.value))
      );
    case 'address_proof':
    case 'aadhar_front':
    case 'aadhar_back':
    case 'passport_front':
    case 'passport_back':
    case 'voter_id_front':
    case 'voter_id_back':
      return canShowAadharDoc(
        context.business_type,
        context.stakeholder?.aadhaar_linked,
        context.stakeholder?.aadhaar_esign_status,
      );
    case 'business_proof_url':
      return isBusinessProofUrlVisible(context);
    case 'business_pan_url':
      return isBusinessPanVisible(context);
    case 'personal_pan':
      return isPersonalPanVisible(context);
    case 'form_80g_url':
    case 'form_12a_url':
      return showForOrgs(
        context.business_overview.business_type.value,
        context.isUpdatedLiteOnboarding,
      );
    case 'bank_prrof':
    case 'cancelled_cheque':
    case 'bank_statement':
      return false;
    // return context.activation_status === 'needs_clarification';
    case 'business_proof':
    case 'gst_certificate':
    case 'msme_certificate':
    case 'shop_establishment_certificate':
      return isBusinessProofTypeDocFieldVisible(context);
    case 'additional_doc':
    case 'amfi_certificate':
    case 'sla_amfi_certificate':
    case 'nbfc_registration_certificate':
    case 'sla_nbfc_registration_certificate':
    case 'irdai_registration_certificate':
    case 'sla_irdai_registration_certificate':
    case 'ffmc_license':
    case 'sla_ffmc_license':
    case 'sebi_registration_certificate':
    case 'sla_sebi_registration_certificate':
    case 'iata_certificate':
    case 'sla_iata_certificate':
    case 'affiliation_certificate':
      return doesHaveAdditionalDocs(context);
    case 'shop_establishment_number':
      return context.shop_establishment_verifiable_zone;
    default:
      return true;
  }
};

const isTabComplete = (data, tab, isUpdatedLiteOnboarding = false) => {
  const tabData = data[tab];
  return Object.keys(tabData).every((key) => {
    if (key === 'bank_account_name' && isUpdatedLiteOnboarding) {
      return true;
    }
    if (key === 'contact_email' && !data?.contact_email && data?.isEmailNonMandatoryOnL1)
      return true;
    if (!isVisible(key, data)) return true;
    if (key === 'gstin' && data.gstin === '' && typeof data.hasGSTIN !== 'boolean') return true;
    return !!tabData[key].value && !tabData[key].error;
  });
};

type State = {
  same_address: boolean;
  has_gstin: boolean;
  has_website_or_app: boolean;
  has_website: boolean;
  has_app: boolean;
  has_non_mandatory_email: boolean;
  is_email_mandatory: boolean;
  active_tab_id: string;
  isFAQOpen: boolean;
  fAQSection: string;
  is_l1_acknowledge: boolean;
  isContactDetailsCompleted: boolean;
  isBusinessOverviewCompleted: boolean;
  isBusinessDetailsCompleted: boolean;
  isBankAndCompanyDetailsCompleted: boolean;
  isDocumentsUploadCompleted: boolean;
  setContactDetailsCompleted: (value: boolean) => void;
  setBusinessOverviewCompleted: (value: boolean) => void;
  setBusinessDetailsCompleted: (value: boolean) => void;
  setBankAndCompanyDetailsCompleted: (value: boolean) => void;
  setDocumentUploadCompleted: (value: boolean) => void;
  setSameAddress: (value: boolean) => void;
  setHasGSTIN: (value: boolean) => void;
  setL1Acknowledge: (value: boolean) => void;
  setHasWebsiteOrApp: (value: boolean) => void;
  setHasWebsite: (value: boolean) => void;
  setHasApp: (value: boolean) => void;
  setHasNonMandatoryEmail: (value: boolean) => void;
  setIsEmailMandatory: (value: boolean) => void;
  setActiveTabId: (value: string) => void;
  setIsFAQOpen: (value: boolean) => void;
  setFAQSection: (value: string) => void;
};

const useActivationFormState = create<State>((set) => ({
  same_address: true,
  has_gstin: false,
  has_website_or_app: false,
  has_website: false,
  has_app: false,
  has_non_mandatory_email: false,
  is_email_mandatory: false,
  active_tab_id: 'contact_details',
  isFAQOpen: false,
  is_l1_acknowledge: false,
  fAQSection: '',
  isContactDetailsCompleted: false,
  isBusinessOverviewCompleted: false,
  isBusinessDetailsCompleted: false,
  isBankAndCompanyDetailsCompleted: false,
  isDocumentsUploadCompleted: false,
  setContactDetailsCompleted: (value) => set({ isContactDetailsCompleted: value }),
  setBusinessOverviewCompleted: (value) => set({ isBusinessOverviewCompleted: value }),
  setBusinessDetailsCompleted: (value) => set({ isBusinessDetailsCompleted: value }),
  setBankAndCompanyDetailsCompleted: (value) => set({ isBankAndCompanyDetailsCompleted: value }),
  setDocumentUploadCompleted: (value) => set({ isDocumentsUploadCompleted: value }),
  setSameAddress: (value) => set({ same_address: value }),
  setHasGSTIN: (value) => set({ has_gstin: value }),
  setL1Acknowledge: (value) => set({ is_l1_acknowledge: value }),
  setHasWebsiteOrApp: (value) => set({ has_website_or_app: value }),
  setHasApp: (value) => set({ has_app: value }),
  setHasNonMandatoryEmail: (value) => set({ has_non_mandatory_email: value }),
  setIsEmailMandatory: (value) => set({ is_email_mandatory: value }),
  setHasWebsite: (value) => set({ has_website: value }),
  setActiveTabId: (value) => set({ active_tab_id: value }),
  setIsFAQOpen: (value) => set({ isFAQOpen: value }),
  setFAQSection: (value) => set({ fAQSection: value }),
}));

export { useActivationFormState, isVisible, isTabComplete };
