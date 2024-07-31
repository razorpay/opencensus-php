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
    },
    merchants: [
      {
        createdAt: '1718211619',
        merchantId: 'OLvMDMRFFdl9TU',
        merchantName: 'CHIZRINZ INFOWAY PRIVATE LIMITED',
        merchantMobile: '+916171928192',
        progressCompletion: '65',
        status: 'PENDING',
      },
      {
        createdAt: '1718210755',
        merchantId: 'OLv6zpwrhtTk7E',
        merchantName: 'Raju Body Building',
        merchantMobile: '+916817163743',
        progressCompletion: '65',
        status: 'UNDER_REVIEW',
      },
    ],
  },
};
