export const POIStatus = {
  failed: {
    poi_verification_status: 'failed',
  },
  pending: {
    poi_verification_status: 'pending',
  },
  not_matched: {
    poi_verification_status: 'not_matched',
  },
  incorrect_details: {
    poi_verification_status: 'incorrect_details',
  },
};

export const activationStatus = {
  mccPending: {
    activation_status: 'activated_mcc_pending',
  },
};

export const activationProgress = {
  activationMccPending: {
    activation_progress: 90,
  },
};

export const OnboardingMileStoneL1 = {
  activation_form_milestone: 'L1',
};

export const OnboardingMileStoneL2 = {
  submitted: true,
};

export const ActivationFlowGG = {
  activation_flow: 'greylist',
  international_activation_flow: 'greylist',
};

export const ActivationFlowGB = {
  activation_flow: 'greylist',
  international_activation_flow: 'blacklist',
};

export const ActivationFlowWW = {
  activation_flow: 'whitelist',
  international_activation_flow: 'whitelist',
};

export const ActivationFlowWG = {
  activation_flow: 'whitelist',
  international_activation_flow: 'greylist',
};

export const ActivationFlowUnreg = {
  activation_flow: 'whitelist',
  international_activation_flow: 'blacklist',
};

export const contactDetails = {
  contact_mobile: '8073945689',
  contact_email: 'fake@gmail.com',
  contact_name: 'Rohan',
};

export const regBusinessOverview = {
  business_type: '1',
  business_website: 'http://www.google.com',
  business_category: 'ecommerce',
  business_subcategory: 'agriculture',
  business_dba: 'Social',
};

export const unregBusinessOverview = {
  business_type: '11',
  business_website: 'http://www.google.com',
  business_category: 'ecommerce',
  business_subcategory: 'agriculture',
  business_dba: 'Social',
};

export const addressDetails = {
  business_registered_address: '1st Floor, 22, SJR Cyber,↵Laskar Hosur Road, Adugodi',
  business_registered_city: 'Bengaluru',
  business_registered_pin: '560030',
  business_registered_state: 'KA',
  business_operation_address: '1st Floor, 22, SJR Cyber, Laskar Hosur Road, Adugodi',
  business_operation_city: 'Bengaluru',
  business_operation_pin: '560030',
  business_operation_state: 'KA',
};

export const businessDetails = {
  business_name: 'other name',
  company_pan: 'AAAAA1234A',
  promoter_pan: 'ARIPH1234C',
  promoter_pan_name: 'TEST',
  ...addressDetails,
};

export const bankAndCompanyDetails = {
  bank_account_name: 'random name',
  bank_account_number: '102938',
  bank_branch_ifsc: 'HDFC0000007',
  company_cin: 'K79807HN8900PGH809707',
  gstin: 'Qwoquiouytyuiip',
};

export const BusinessModelPicker = {
  business_subcategory: 'others',
};

export const PaymentEnable = {
  activated: true,
};

export const Documents = {
  documents: {
    aadhar_back: [
      {
        id: 'HYOU9XMj32ZZXF',
        file_store_id: 'HYOU9hrP2FNh07',
        merchant_id: 'HYOL66VVU5SpIO',
        created_at: 1626174666,
      },
    ],
    aadhar_front: [
      {
        id: 'HYORFCcwPHH0nt',
        file_store_id: 'HYORFP1NMI6HNC',
        merchant_id: 'HYOL66VVU5SpIO',
        created_at: 1626174501,
      },
    ],
    msme_certificate: [
      {
        id: 'HYOUVQ6CaZEOQD',
        file_store_id: 'HYOUVZHDDzRd9S',
        merchant_id: 'HYOL66VVU5SpIO',
        created_at: 1626174686,
      },
    ],
    business_proof_url: [
      {
        id: 'HYOUVQ6CaZEOQD',
        file_store_id: 'HYOUVZHDDzRd9S',
        merchant_id: 'HYOL66VVU5SpIO',
        created_at: 1626174686,
      },
    ],
    business_pan_url: [
      {
        id: 'HYOUVQ6CaZEOQD',
        file_store_id: 'HYOUVZHDDzRd9S',
        merchant_id: 'HYOL66VVU5SpIO',
        created_at: 1626174686,
      },
    ],
    personal_pan: [
      {
        id: 'HYOUNzdKcwxUUJ',
        file_store_id: 'HYOUOARuDHvS9M',
        merchant_id: 'HYOL66VVU5SpIO',
        created_at: 1626174679,
      },
    ],
  },
};
