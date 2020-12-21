import create from 'zustand';
import {
  isUnregisteredBusiness,
  CIN_BusinessTypes,
  LLPIN_BusinessTypes,
} from '../../mobile/Constants/OnboardingConstants';

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
        !isUnregisteredBusiness(context.business_overview.business_type.value) && !context.hasGSTIN
      );
    case 'company_cin':
      return (
        CIN_BusinessTypes.includes(Number(context.business_overview.business_type.value)) ||
        LLPIN_BusinessTypes.includes(Number(context.business_overview.business_type.value))
      );
    default:
      return true;
  }
};

const isTabComplete = (data, tab) => {
  const tabData = data[tab];
  return Object.keys(tabData).every((key) => {
    if (!isVisible(key, data)) return true;
    return !!tabData[key].value && !tabData[key].error;
  });
};

type State = {
  same_address: boolean;
  has_gstin: boolean;
  has_website: boolean;
  active_tab_id: string;
  isContactDetailsCompleted: boolean;
  isBusinessOverviewCompleted: boolean;
  isBusinessDetailsCompleted: boolean;
  isBankAndCompanyDetailsCompleted: boolean;
  isDocumentsUploadCompleted: boolean;
  setContactDetailsCompleted: (value: boolean) => void;
  setBusinessOverviewCompleted: (value: boolean) => void;
  setBusinessDetailsCompleted: (value: boolean) => void;
  setBankAndCompanyDetailsCompleted: (value: boolean) => void;
  setSameAddress: (value: boolean) => void;
  setHasGSTIN: (value: boolean) => void;
  setHasWebsite: (value: boolean) => void;
  setActiveTabId: (value: string) => void;
};

const useActivationFormState = create<State>((set) => ({
  same_address: false,
  has_gstin: false,
  has_website: false,
  active_tab_id: 'contact_details',
  isContactDetailsCompleted: false,
  isBusinessOverviewCompleted: false,
  isBusinessDetailsCompleted: false,
  isBankAndCompanyDetailsCompleted: false,
  isDocumentsUploadCompleted: false,
  setContactDetailsCompleted: (value) => set({ isContactDetailsCompleted: value }),
  setBusinessOverviewCompleted: (value) => set({ isBusinessOverviewCompleted: value }),
  setBusinessDetailsCompleted: (value) => set({ isBusinessDetailsCompleted: value }),
  setBankAndCompanyDetailsCompleted: (value) => set({ isBankAndCompanyDetailsCompleted: value }),
  setSameAddress: (value) => set({ same_address: value }),
  setHasGSTIN: (value) => set({ has_gstin: value }),
  setHasWebsite: (value) => set({ has_website: value }),
  setActiveTabId: (value) => set({ active_tab_id: value }),
}));

export { useActivationFormState, isVisible, isTabComplete };
