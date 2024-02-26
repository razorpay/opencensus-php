import React from 'react';

import PartnerPricingPlans from 'merchant/views/PartnerDashboard/external/PartnerPricingPlans';
import { render, screen, userEvent, waitForLoadingToFinishByLabel } from 'test-utils';

import { useDefaultPartnerPricingHandler } from './mocks/once-handlers';

const defaultProps = {};
describe('PartnerPricingPlans', () => {
  const renderApp = (props = {}) => {
    // eslint-disable-next-line
    // @ts-ignore
    render(<PartnerPricingPlans {...defaultProps} {...props} />);
  };
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render default partner pricing', async () => {
    useDefaultPartnerPricingHandler();
    renderApp();
    await waitForLoadingToFinishByLabel();
    expect(screen.getByText('openwallet')).not.toBeVisible();
    await userEvent.click(screen.getByText('Method: Wallet'));
    expect(screen.getByText('openwallet')).toBeVisible();
  });
});
