import React from 'react';
import { rest } from 'msw';
import { storeWithInitialState } from 'merchant/store';
import { fireEvent, render as renderMain, screen, server, waitFor } from 'test-utils';

import BasicCOD from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Containers/BasicCOD';
import { ConfirmationModalProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

import { setProfile, clearProfile } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import {
  shipping_profiles,
  magic_settings,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/__tests__/mocks';
import { act } from 'react-dom/test-utils';

const initState = {
  magic_settings,
  magicShippingEngine: {
    shipping_profiles: {},
    isLoading: { summary: false },
  },
};

const render = (ui, config = {}) => {
  return renderMain(<ConfirmationModalProvider>{ui}</ConfirmationModalProvider>, {
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

describe('Sync Profiles', () => {
  test('Should not render Sync Now Button and Last Synced Badge if COD is false', () => {
    const magicSettings = { rcod: { enabled: false } };
    render(<BasicCOD />, { magic_settings: magicSettings });
    expect(
      screen.queryByRole('button', { name: 'Sync profiles from Shopify' }),
    ).not.toBeInTheDocument();
    expect(screen.queryByTestId('last-synced-badge')).not.toBeInTheDocument();
  });

  test('Should render Sync Now Button if COD is true and Last Synced Badge should be null if shipping profiles are empty', () => {
    render(<BasicCOD />);
    expect(
      screen.queryByRole('button', { name: 'Sync profiles from Shopify' }),
    ).toBeInTheDocument();
    expect(screen.queryByTestId('last-synced-badge')).not.toBeInTheDocument();
  });

  test('Should render Sync Now Button if cod is true and Last Synced Badge should not be N/A if shipping profiles are not empty', () => {
    render(<BasicCOD />, shipping_profiles);
    expect(
      screen.queryByRole('button', { name: 'Sync profiles from Shopify' }),
    ).toBeInTheDocument();
    //Not asserting the time here due to relative nature , covered this in helpers test
    expect(screen.queryByTestId('last-synced-badge')).toBeInTheDocument();
  });

  test('Should display confirmation modal on initial sync button click & should show updated sync status after submitting confirmation modal', () => {
    render(<BasicCOD />);
    act(() => {
      fireEvent.click(screen.getByRole('button', { name: 'Sync profiles from Shopify' }));
    });
    expect(
      screen.getByText(/Syncing Shipping Profiles from Shopify might take upto 20 seconds./i),
    ).toBeInTheDocument();
  });

  test('Should show updated sync status on sync btn & table shimmer(skeleton View) after submitting sync confirmation modal', async () => {
    render(<BasicCOD />);
    expect(screen.queryByRole('button', { name: 'Sync profiles from Shopify' })).toHaveTextContent(
      /Sync Again/i,
    );
    act(() => {
      fireEvent.click(screen.getByRole('button', { name: 'Sync profiles from Shopify' }));
    });
    expect(
      screen.getByText(/Syncing Shipping Profiles from Shopify might take upto 20 seconds./i),
    ).toBeInTheDocument();
    expect(screen.queryByTestId('Table-Shimmer')).not.toBeInTheDocument();

    act(() => {
      fireEvent.click(screen.getByRole('button', { name: 'confirm sync' }));
    });
    jest.advanceTimersByTime(6000);

    await waitFor(() => {
      expect(screen.queryByTestId('Table-Shimmer')).toBeInTheDocument();
      expect(
        screen.queryByRole('button', { name: 'Sync profiles from Shopify' }),
      ).toHaveTextContent(/Syncing from Shopify/i);
      expect(screen.getByTestId('Table-Shimmer')).toHaveTextContent(
        /Please wait until sync is complete/i,
      );
    });
  });

  test('Should notify about invalid status code if sync with shopify api returns status code other than 202', async () => {
    server.use(
      rest.post('*/magic/shipping/shopify/sync', (req, res, ctx) => {
        return res(
          ctx.json({
            status_code: 204,
            success: true,
            data: [],
          }),
        );
      }),
    );
    render(<BasicCOD />);
    expect(
      screen.queryByText(/Unexpected Status Code. Please try again later./i),
    ).not.toBeInTheDocument();
    act(() => {
      fireEvent.click(screen.getByRole('button', { name: 'Sync profiles from Shopify' }));
    });
    expect(screen.queryByText(/Syncing from Shopify/i)).not.toBeInTheDocument();
    act(() => {
      fireEvent.click(screen.getByRole('button', { name: 'confirm sync' }));
    });
    await waitFor(() => {
      expect(
        screen.getByText(/Unexpected Status Code. Please try again later./i),
      ).toBeInTheDocument();
    });
  });
});
