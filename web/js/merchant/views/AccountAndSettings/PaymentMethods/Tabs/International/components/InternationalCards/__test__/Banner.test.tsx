import Banner from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner/Banner';
import {
  getBannerProps,
  trackBannerDisplayed,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner/config';
import {
  BannerType,
  ICProductStates,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

import React from 'react';
import { render, screen } from 'test-utils';
import { omit } from 'lodash';

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner/config',
  () => ({
    ...(jest.requireActual(
      'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner/config',
    ) as Record<string, unknown>),
    getBannerProps: jest.fn().mockImplementation((args) => args),
    trackBannerDisplayed: jest.fn(),
  }),
);

jest.mock('@razorpay/blade/components', () => {
  const bladeActual = jest.requireActual('@razorpay/blade/components');
  return {
    __esModule: true,
    ...bladeActual,
    Alert: ({ type, workflowEta, bannerMessage }) => (
      <div>
        type: {type}
        workflowEta: {workflowEta}
        bannerMessage: {bannerMessage}
      </div>
    ),
  };
});

const defaultProps = {
  type: BannerType.APPROVED,
  bannerMessage: 'test',
  pgProductState: ICProductStates.ACTION_REQUIRED,
  ppliProductState: ICProductStates.ACTIVE,
  workflowEta: 'test',
};

const renderApp = (props = {}) =>
  render(<Banner {...defaultProps} {...props} isRequestRejectedFor90Days={false} />);

describe('Banner', () => {
  test('should render Banner component and call trackBannerDisplayed on mount', () => {
    renderApp();
    expect(trackBannerDisplayed).toHaveBeenCalledWith(omit(defaultProps, 'workflowEta'));
    expect(getBannerProps).toHaveBeenCalledWith(
      expect.objectContaining({
        ...omit(defaultProps, 'pgProductState', 'ppliProductState'),
      }),
    );
    expect(screen.getByText(new RegExp(`type: ${defaultProps.type}`))).toBeInTheDocument();
    expect(screen.getByText(/workflowEta: test/)).toBeInTheDocument();
    expect(
      screen.getByText(new RegExp(`bannerMessage: ${defaultProps.bannerMessage}`)),
    ).toBeInTheDocument();
  });
});
