export const initialState = {
  session: {
    user: {
      isUnregisteredBusiness: false,
      isOrgRZP: true,
      business_registered_address: 'L17110MH1973PLC019786',
      business_registered_address_l2: null,
      business_operation_city: 'K V Rangareddy',
      business_operation_district: null,
      business_registered_state: 'TG',
      business_registered_country: null,
      business_registered_pin: '500035',
      isAllowedEdit: (_) => true,
    },
  },
  profile: {
    merchant_gst: {},
    rzp_gst: {
      gstin: '29AAGCR4375J1ZU',
    },
  },
};
