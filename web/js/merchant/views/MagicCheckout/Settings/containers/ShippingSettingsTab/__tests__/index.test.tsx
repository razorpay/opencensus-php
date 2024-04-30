import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { INITIAL_STATE } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';

import ShippingSettingsTab from '..';

const renderShippingSettingsTab = (newProps = {}) => {
  return render(
    <Provider store={storeWithInitialState({ ...INITIAL_STATE })}>
      <BladeProvider themeTokens={bladeTheme}>
        <ShippingSettingsTab {...newProps} />
      </BladeProvider>
    </Provider>,
  );
};

describe('Shipping Settings Tab', () => {
  test('Should render tab', () => {
    renderShippingSettingsTab();
    const settings = screen.queryByText(/Shipping Settings/i);
    const profiles = screen.queryByText('Shipping Profiles');
    waitFor(() => {
      const sliders = screen.getAllByTestId('profile-slider');
      expect(settings).toBeInTheDocument();
      expect(profiles).toBeInTheDocument();
      expect(sliders).toHaveLength(2);
    });
  });

  test('should not show Magic shipping toggle in case of MagicX', () => {
    renderShippingSettingsTab();
    expect(screen.queryByText(/Shipping Settings/i)).toBeInTheDocument();
    expect(screen.queryByText(/^Magic shipping?/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Choose where to ship your orders./i)).toBeInTheDocument();
    expect(
      screen.queryByText(
        /Note: Update these settings as well whenever you change something in Shopify Shipping./i,
      ),
    ).toBeInTheDocument();
  });
});
