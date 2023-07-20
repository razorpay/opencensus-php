import create from 'zustand';
import {
  isUnregisteredBusiness,
  isPersonalPanVisible,
} from 'merchant/views/onboarding/mobile/services/utils';

const isVisible = (fieldName, context) => {
  switch (fieldName) {
    case 'company_pan':
      return !['11', '2', '1'].includes(context.business_details.business_type.value);
    case 'business_name':
      return !isUnregisteredBusiness(context.business_details.business_type.value);
    case 'gstin':
      return !isUnregisteredBusiness(context.business_details.business_type.value);
    case 'personal_pan':
      return isPersonalPanVisible(context);
    default:
      return true;
  }
};

const isTabComplete = (data, tab) => {
  const tabData = data[tab];
  return Object.keys(tabData).every((key) => {
    if (!isVisible(key, data)) return true;
    if (key === 'gstin' && data.gstin === '' && typeof data.hasGSTIN !== 'boolean') return true;
    return !!tabData[key].value && !tabData[key].error;
  });
};

const useActivationFormState = create((set) => ({
  has_gstin: false,
  active_tab_id: 'contact_details',
  isContactDetailsCompleted: false,
  isBusinessDetailsCompleted: false,
  isAddressDetailsCompleted: false,
  setContactDetailsCompleted: (value) => set({ isContactDetailsCompleted: value }),
  setBusinessDetailsCompleted: (value) => set({ isBusinessDetailsCompleted: value }),
  setAddressDetailsCompleted: (value) => set({ isAddressDetailsCompleted: value }),
  setHasGSTIN: (value) => set({ has_gstin: value }),
  setActiveTabId: (value) => set({ active_tab_id: value }),
}));

export { useActivationFormState, isTabComplete, isVisible };
