import {
  PosSubmerchantDetailsResponseDataType,
  kycHistoryObjectType,
  actionStateType,
} from 'merchant/views/PartnerDashboard/SubMerchant/POS/TypeDeclares';

interface posSubmerchantDetailsMockResponseDataType {
  status_code: number;
  success: boolean;
  data: PosSubmerchantDetailsResponseDataType;
}

export const actionStateResponse: actionStateType = [
  {
    admin_id: 'Jz9cQaFeNKHvGF',
    merchant_id: 'NOxjMvd6jJFXrX',
    name: 'submitted',
    metadata: {
      actor_id: 'Jz9cQaFeNKHvGF',
      actor_email: 'rzpadmin@razorpay.com',
      actor_name: 'KMK admin',
      actor_role: 'admin',
    },
    created_at: '1663128019',
    updated_at: '1706515918',
  },
  {
    admin_id: 'Jz9cQaFeNKHvGF',
    merchant_id: 'NOxjMvd6jJFXrX',
    name: 'needs_clarification',
    metadata: {
      actor_id: 'Jz9cQaFeNKHvGF',
      actor_email: 'rzpadmin@razorpay.com',
      actor_name: 'KMK admin',
      actor_role: 'admin',
    },
    created_at: '1663228019',
    updated_at: '1706515918',
  },
  {
    admin_id: '',
    merchant_id: 'NOxjMvd6jJFXrX',
    name: 'under_review',
    metadata: {
      actor_id: 'Jz9cQaFeNKHvGX',
      actor_email: 'agent@razorpay.com',
      actor_name: 'KMK agent',
      actor_role: 'partner_agent',
    },
    created_at: '1706514918',
    updated_at: '1706514918',
  },
  {
    admin_id: 'Jz9cQaFeNKHvGF',
    merchant_id: 'NOxjMvd6jJFXrX',
    name: 'needs_clarification',
    metadata: {
      actor_id: 'Jz9cQaFeNKHvGF',
      actor_email: 'rzpadmin@razorpay.com',
      actor_name: 'KMK admin',
      actor_role: 'admin',
    },
    created_at: '1663229019',
    updated_at: '1706515918',
  },
  {
    admin_id: 'Jz9cQaFeNKHvGF',
    merchant_id: 'NOxjMvd6jJFXrX',
    name: 'activated',
    metadata: {
      actor_id: 'Jz9cQaFeNKHvGF',
      actor_email: 'rzpadmin@razorpay.com',
      actor_name: 'KMK admin',
      actor_role: 'admin',
    },
    created_at: '1663329019',
    updated_at: '1706515918',
  },
];

export const actionStateResponseWithRejected: actionStateType = [
  {
    admin_id: 'Jz9cQaFeNKHvGF',
    merchant_id: 'NOxjMvd6jJFXrX',
    name: 'submitted',
    metadata: {
      actor_id: 'Jz9cQaFeNKHvGF',
      actor_email: 'rzpadmin@razorpay.com',
      actor_name: 'KMK admin',
      actor_role: 'admin',
    },
    created_at: '1663128019',
    updated_at: '1706515918',
  },
  {
    admin_id: 'Jz9cQaFeNKHvGF',
    merchant_id: 'NOxjMvd6jJFXrX',
    name: 'rejected',
    metadata: {
      actor_id: 'Jz9cQaFeNKHvGF',
      actor_email: 'rzpadmin@razorpay.com',
      actor_name: 'KMK admin',
      actor_role: 'admin',
    },
    created_at: '1663228019',
    updated_at: '1706515918',
  },
];
export const posSubmerchantDetailsResponse: posSubmerchantDetailsMockResponseDataType = {
  status_code: 200,
  success: true,
  data: {
    id: 'acc_NOxjMvd6jJFXrX',
    entity: 'merchant',
    name: 'Kunal Test',
    email: 'kunal.chawla+2024@razorpay.com',
    partner_type: null,
    created_at: 1705337905,
    details: {
      activation_status: 'needs_clarification',
      kyc_clarification_reasons: {
        nc_count: 1,
        additional_details: [],
        clarification_reasons_v2: {
          aadhar_front: [
            {
              from: 'admin',
              nc_count: 1,
              created_at: '1663228017',
              is_current: true,
              reason_code: 'illegible_doc',
              reason_type: 'predefined',
            },
            {
              from: 'admin',
              nc_count: 1,
              created_at: '1663229017',
              is_current: true,
              reason_code: 'test_case',
              reason_type: 'predefined',
            },
          ],
          aadhar_back: [
            {
              from: 'admin',
              nc_count: 1,
              created_at: '1663229017',
              is_current: true,
              reason_code: 'test_case',
              reason_type: 'predefined',
            },
          ],
        },
      },
    },
    user: {
      id: 'NOxjNxJ347WNO4',
      name: 'Kunal Test',
      email: 'kunal.chawla+2024@razorpay.com',
      contact_mobile: '8143639996',
      contact_mobile_verified: false,
    },
    dashboard_access: true,
    application: {
      id: 'Jz9cQaFeNKHvGE',
    },
    kyc_access: null,
    product: {
      NOxjMvd6jJFXrX: ['primary'],
    },
    pos: {
      activation_status: 'needs_clarification',
      success: true,
      action_states: actionStateResponse,
      last_kyc_performed_by: {
        contact_email: 'kmk@rzp.com',
        contact_no: '8143639996',
        name: 'KMK',
        type: 'admin',
        id: 'Jz9cQaFeNKHvGA',
      },
    },
  },
};

export const posKycHistoryData: kycHistoryObjectType[] = [
  {
    status: 'submitted',
    date: 'Aug 20, 10:07am',
    performedBy: 'Test User',
  },
  {
    status: 'under_review',
    date: 'Aug 20, 10:07am',
    performedBy: 'Test User',
  },
  {
    status: 'needs_clarification',
    date: 'Aug 20, 10:07am',
    performedBy: 'Test User',
    reason: [{ field: 'aadhaar front', reason: 'Illegible doc' }],
  },
  {
    status: 'rejected',
    date: 'Aug 20, 10:07am',
    performedBy: 'Test User',
  },
];
