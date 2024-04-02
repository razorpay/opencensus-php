import store from 'merchant/store';

const globalStore = store.getState();
export const MOCK_USER_FOR_POS_NC = {
  ...globalStore.session.user,
  id: 'mock-user-id',
  business_operation_address: 'test operation address',
  business_operation_city: 'test operation city',
  business_operation_state: 'test operation state',
  business_operation_pin: '123456',
  business_registered_address: 'test registered address',
  business_registered_city: 'test registered city',
  business_registered_pin: '123456',
  business_registered_state: 'test registered state',
  contact_mobile: '1234567890',
  contact_name: 'Test Name',
  contact_email: 'testemail@gmail.com',
  merchant: {
    id: 'mock-user-id',
  },
  merchant_business_detail: {
    website_details: {
      physical_store: true,
    },
  },
  activation_status: 'under_review',
  pos_activation_status: 'needs_clarification',
  pos_activation_flow: 'whitelist',
  is_pgos_merchant: true,
  business_type: '1',
  instantActivation: {
    isWhitelistFlow: true,
  },
  activation_form_milestone: 'L2',
};
