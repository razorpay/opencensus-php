import React from 'react';
import { storeWithInitialState } from 'merchant/store';
import { fireEvent, render as renderMain, screen, waitFor } from 'test-utils';

import BasicCOD from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Containers/BasicCOD';

import { setProfile, clearProfile } from 'merchant/reducers/magicCheckout/shippingEngine/action';

import {
  shipping_profiles,
  magic_settings,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/__tests__/mocks';

const initState = {
  magic_settings,
  magicShippingEngine: {
    shipping_profiles: {},
    isLoading: { summary: false },
  },
};

const render = (ui, config = {}) => {
  return renderMain(ui, {
    reduxStore: storeWithInitialState({ ...initState, ...config }),
  });
};

jest.mock('merchant/reducers/magicCheckout/shippingEngine/action', () => ({
  setProfile: jest.fn((profileName) => ({
    type: 'SET_PROFILE',
    payload: { profileName },
  })),
  clearProfile: jest.fn(() => ({
    type: 'CLEAR_PROFILE',
  })),
}));

describe('BasicCOD Component', () => {
  test('Should Render and display a message incase of no shipping profiles', () => {
    render(<BasicCOD />);
    expect(screen.getByText(/Configure COD for Shopify Shipping Methods/i)).toBeInTheDocument();
    expect(screen.getByText(/Shipping Methods Not Found On Shopify/i)).toBeInTheDocument();
  });

  test('Should Render shipping methods when shipping profiles data is present', () => {
    render(<BasicCOD />, shipping_profiles);
    expect(screen.getByText(/Profile1-zone1/i)).toBeInTheDocument();
  });

  test('Should not render shipping methods table if COD is disabled', () => {
    const magicSettings = { rcod: { enabled: false } };
    render(<BasicCOD />, { magic_settings: magicSettings });

    expect(
      screen.queryByText(/Configure COD for Shopify Shipping Methods/i),
    ).not.toBeInTheDocument();
    expect(screen.queryByRole('rowheader')).not.toBeInTheDocument();
  });

  test('Should open modal on edit button click , sets the profile to be edited and clears the profile on modal close', async () => {
    render(<BasicCOD />, shipping_profiles);
    // clicking the edit button
    fireEvent.click(screen.getByText('Configure COD'));
    await waitFor(() => {
      expect(setProfile).toHaveBeenCalledWith('Profile1');
      expect(screen.getByRole('dialog')).toBeInTheDocument(); // Checks if the modal opens
    });
    fireEvent.click(screen.getByText('Cancel'));
    await waitFor(() => {
      expect(clearProfile).toHaveBeenCalledTimes(1);
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument(); // Checks if the modal closes
    });
  });
});
