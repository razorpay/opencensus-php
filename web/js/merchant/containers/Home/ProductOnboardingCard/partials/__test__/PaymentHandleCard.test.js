import React from 'react';
import { render } from 'test-utils';
import PaymentHandleCard from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentHandleCard';

const paymentHandleData = {
  paymentHandleUrl: 'https://razorpay.me@chonkyfoodpanda',
  paymentHandleSlug: '@chonkyfoodpanda',
};

describe('ProductOnboardingCard - PaymentHandleCard', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });
  test('should render component without errors', () => {
    expect(() =>
      render(
        <PaymentHandleCard history={history} product="PH" paymentHandleData={paymentHandleData} />,
      ),
    ).not.toThrowError();
  });

  test('should show correct payment handle', () => {
    const { queryByText } = render(
      <PaymentHandleCard history={history} product="PH" paymentHandleData={paymentHandleData} />,
    );
    expect(queryByText(paymentHandleData.paymentHandleUrl)).toBeVisible();
  });
});
