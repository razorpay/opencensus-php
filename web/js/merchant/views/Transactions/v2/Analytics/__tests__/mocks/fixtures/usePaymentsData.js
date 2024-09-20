import React, { useEffect } from 'react';
import { render } from 'test-utils';

import { usePaymentsData } from 'merchant/views/Transactions/v2/Analytics/hooks';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';

const App = ({ dataCallback, isRefundPendingEnabled }) => {
  const { paymentsData, fetchPaymentData, loading, failed } = usePaymentsData({
    isRefundPendingEnabled,
  });
  useEffect(() => {
    if (dataCallback) {
      dataCallback({
        paymentsData,
      });
    }
  }, [paymentsData]);
  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {failed && <div data-testid="failed">Failed...</div>}
      <button onClick={() => fetchPaymentData({})}>Fetch</button>
    </div>
  );
};

export const renderApp = (props = {}) => {
  render(<App {...props} />);
};

export const paymentsApiResponse = {
  paymentcount: {
    total: 2,
    last_updated_at: 1690494299,
    result: [
      {
        status: 'captured',
        value: 2,
      },
      {
        status: 'refunded',
        value: 7,
      },
    ],
  },
  paymentsum: {
    total: 2,
    last_updated_at: 1690494299,
    result: [
      {
        status: 'captured',
        value: 88000,
      },
      {
        status: 'refunded',
        value: 304200,
      },
    ],
  },
  refundcountnormal: {
    total: 1,
    last_updated_at: 1681799519,
    result: [
      {
        status: 'processed',
        value: 7,
      },
    ],
  },
  refundsumnormal: {
    total: 1,
    last_updated_at: 1681799519,
    result: [
      {
        status: 'processed',
        value: 304200,
      },
    ],
  },
  refundcountinstant: {
    last_updated_at: 1681799519,
    result: [],
  },
  refundsuminstant: {
    last_updated_at: 1681799519,
    result: [],
  },
  paymentbymethod: {
    total: 5,
    last_updated_at: 1690494299,
    result: [
      {
        method: 'cod',
        value: 6794974,
      },
      {
        method: 'netbanking',
        value: 386774,
      },
      {
        method: 'upi',
        value: 354387,
      },
      {
        method: 'card',
        value: 136500,
      },
      {
        method: 'wallet',
        value: 13000,
      },
    ],
  },
};

export const mockServerResponsePayload = {
  top_4_methods: {
    kind: 'success',
    data: {
      ...paymentsApiResponse,
      paymentbymethod: {
        total: 4,
        last_updated_at: 1690494299,
        result: [
          {
            method: 'upi',
            value: 354387,
          },
          {
            method: 'card',
            value: 136500,
          },
          {
            method: 'wallet',
            value: 13000,
          },
          {
            method: 'cod',
            value: 6794974,
          },
        ],
      },
    },
  },
  zero_value_methods: {
    kind: 'success',
    data: {
      ...paymentsApiResponse,
      paymentbymethod: {
        total: 4,
        last_updated_at: 1690494299,
        result: [
          {
            method: 'upi',
            value: 354387,
          },
          {
            method: 'card',
            value: 136500,
          },
          {
            method: 'wallet',
            value: 13000,
          },
          {
            method: 'cod',
            value: 0,
          },
          {
            method: 'emi',
            value: 0,
          },
        ],
      },
    },
  },
  no_data: {
    kind: 'success',
    data: {
      paymentcount: {
        total: 2,
        last_updated_at: 1690494299,
        result: null,
      },
      paymentsum: {
        total: 2,
        last_updated_at: 1690494299,
        result: null,
      },
      refundcountnormal: {
        total: 1,
        last_updated_at: 1681799519,
        result: [],
      },
      refundsumnormal: {
        total: 1,
        last_updated_at: 1681799519,
        result: [],
      },
      refundcountinstant: {
        last_updated_at: 1681799519,
        result: [],
      },
      refundsuminstant: {
        last_updated_at: 1681799519,
        result: [],
      },
      paymentbymethod: {
        total: 5,
        last_updated_at: 1690494299,
        result: null,
      },
    },
  },
  no_capture_data: {
    kind: 'success',
    data: {
      ...paymentsApiResponse,
      paymentcount: {
        total: 2,
        last_updated_at: 1690494299,
        result: [
          {
            status: 'refunded',
            value: 7,
          },
        ],
      },
      paymentsum: {
        total: 2,
        last_updated_at: 1690494299,
        result: [
          {
            status: 'refunded',
            value: 304200,
          },
        ],
      },
    },
  },
};

export const expectedHookResponse = {
  withRefundPendingFeatureDisabled: {
    paymentsData: {
      paymentCapturedCount: 2,
      paymentCapturedAmount: 88000,
      refundCount: 7,
      refundAmount: 304200,
      paymentByMethod: [
        { label: 'cod', value: i18nifyConvertToMajorUnit(6794974, 'INR') },
        { label: 'netbanking', value: i18nifyConvertToMajorUnit(386774, 'INR') },
        { label: 'upi', value: i18nifyConvertToMajorUnit(354387, 'INR') },
        { label: 'Others', value: i18nifyConvertToMajorUnit(149500, 'INR') },
      ],
    },
  },
  withRefundPendingFeatureEnabled: {
    paymentsData: {
      paymentCapturedCount: 2,
      paymentCapturedAmount: 88000,
      refundCount: 7,
      refundAmount: 304200,
      paymentByMethod: [
        { label: 'cod', value: i18nifyConvertToMajorUnit(6794974, 'INR') },
        { label: 'netbanking', value: i18nifyConvertToMajorUnit(386774, 'INR') },
        { label: 'upi', value: i18nifyConvertToMajorUnit(354387, 'INR') },
        { label: 'Others', value: i18nifyConvertToMajorUnit(149500, 'INR') },
      ],
    },
  },
  top_4_methods: {
    paymentsData: {
      paymentCapturedCount: 2,
      paymentCapturedAmount: 88000,
      refundCount: 7,
      refundAmount: 304200,
      paymentByMethod: [
        { label: 'cod', value: i18nifyConvertToMajorUnit(6794974, 'INR') },
        { label: 'upi', value: i18nifyConvertToMajorUnit(354387, 'INR') },
        { label: 'card', value: i18nifyConvertToMajorUnit(136500, 'INR') },
        { label: 'wallet', value: i18nifyConvertToMajorUnit(13000, 'INR') },
      ],
    },
  },
  zero_value_methods: {
    paymentsData: {
      paymentCapturedCount: 2,
      paymentCapturedAmount: 88000,
      refundCount: 7,
      refundAmount: 304200,
      paymentByMethod: [
        { label: 'upi', value: i18nifyConvertToMajorUnit(354387, 'INR') },
        { label: 'card', value: i18nifyConvertToMajorUnit(136500, 'INR') },
        { label: 'wallet', value: i18nifyConvertToMajorUnit(13000, 'INR') },
      ],
    },
  },
  no_data: {
    paymentsData: {
      paymentCapturedCount: 0,
      paymentCapturedAmount: 0,
      refundCount: 0,
      refundAmount: 0,
      paymentByMethod: [],
    },
  },
  no_capture_data: {
    paymentsData: {
      paymentCapturedCount: 0,
      paymentCapturedAmount: 0,
      refundCount: 7,
      refundAmount: 304200,
      paymentByMethod: [
        { label: 'cod', value: i18nifyConvertToMajorUnit(6794974, 'INR') },
        { label: 'netbanking', value: i18nifyConvertToMajorUnit(386774, 'INR') },
        { label: 'upi', value: i18nifyConvertToMajorUnit(354387, 'INR') },
        { label: 'Others', value: i18nifyConvertToMajorUnit(149500, 'INR') },
      ],
    },
  },
};
