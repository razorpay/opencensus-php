export const SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE = {
  salesOnboardedMerchants: {
    __typename: 'SalesOnboardedMerchants',
    limit: 10,
    offset: 0,
    total: 31,
    hasMore: true,
    statusCounts: {
      activated: 20,
      pending: 10,
      underReview: 1,
      rejected: 21,
      kycQualifiedStb: 35,
      pricingNeedsClarification: 1,
      needsClarification: 7,
    },
    merchants: [
      {
        createdAt: '1718211619',
        merchantId: 'OLvMDMRFFdl9TU',
        merchantName: 'CHIZRINZ INFOWAY PRIVATE LIMITED',
        merchantMobile: '+916171928192',
        progressCompletion: '65',
        pricingNcStatus: '',
        status: 'PENDING',
      },
      {
        createdAt: '1718210755',
        merchantId: 'OLv6zpwrhtTk7E',
        merchantName: 'Raju Body Building',
        merchantMobile: '+916817163743',
        progressCompletion: '65',
        pricingNcStatus: '',
        status: 'UNDER_REVIEW',
      },
      {
        createdAt: '1718210755',
        merchantId: 'OLv6zpwrhtTk7E',
        merchantName: 'Raju Body Building',
        merchantMobile: '+916817163743',
        progressCompletion: '65',
        pricingNcStatus: 'pending_agent_action',
        status: 'UNDER_REVIEW',
      },
    ],
  },
};

export const SUCCESS_SALES_MAPPED_MERCHANTS_EMPTY_RESPONSE = {
  salesOnboardedMerchants: {
    __typename: 'SalesOnboardedMerchants',
    limit: 10,
    offset: 0,
    total: 31,
    hasMore: true,
    statusCounts: {
      activated: 0,
      pending: 0,
      underReview: 0,
      rejected: 0,
      kycQualifiedStb: 0,
    },
    merchants: [],
  },
};
