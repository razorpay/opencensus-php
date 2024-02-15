import { PRODUCT_TYPE, ADD_MODE } from 'merchant/views/PartnerDashboard/constants';
export const defaultAddMerchantState = {
  file_id: '',
  addMode: ADD_MODE.single,
  bulkContactsCount: 0,
  step: 1,
  merchantType: PRODUCT_TYPE.PG,
  merchantEmail: '',
  merchantName: '',
  merchantContact: '',
  referralData: '',
  isFormValid: false,
};
export const addMerchantProps = {
  user: {},
  addType: PRODUCT_TYPE.CAPITAL,
};
