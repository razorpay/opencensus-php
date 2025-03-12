import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import * as storefrontReducers from 'merchant/reducers/paymentPages/storefront';
import { storefrontData } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/mockData';
import AddBannerDetails from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/BannerDetails/AddBannerDetails';

describe('AddBannerDetails Component', () => {
  const editStorefront = jest.spyOn(storefrontReducers, 'editStorefront');
  const mockSetShowBannerAlert = jest.fn();
  const mockHandleClick = jest.fn();

  it('renders LineItems when openAddBannerDrawer is false', () => {
    render(
      <AddBannerDetails
        handleClick={mockHandleClick}
        openAddBannerDrawer={false}
        showBannerAlert={false}
        setShowBannerAlert={mockSetShowBannerAlert}
      />,
    );

    expect(screen.getByText(/Add store banner/i)).toBeInTheDocument();
  });

  it('renders alert when showBannerAlert is true', () => {
    render(
      <AddBannerDetails
        handleClick={mockHandleClick}
        openAddBannerDrawer={false}
        showBannerAlert={true}
        setShowBannerAlert={mockSetShowBannerAlert}
      />,
    );

    expect(screen.getByText(/Missing Banner Image/i)).toBeInTheDocument();
  });

  it('renders alert and handles primary button click', () => {
    render(
      <AddBannerDetails
        handleClick={mockHandleClick}
        openAddBannerDrawer={false}
        showBannerAlert={true}
        setShowBannerAlert={mockSetShowBannerAlert}
      />,
    );

    expect(screen.getByText(/Missing Banner Image/i)).toBeInTheDocument();

    const primaryButton = screen.getByText('Upload Banner');
    fireEvent.click(primaryButton);

    expect(mockSetShowBannerAlert).toHaveBeenCalled();
    const updateFunction = mockSetShowBannerAlert.mock.calls[0][0];
    const previousState = { showSocialHandleAlert: true, showBannerAlert: true };
    const result = updateFunction(previousState);
    expect(result).toEqual({
      showSocialHandleAlert: true,
      showBannerAlert: false,
    });

    expect(editStorefront).toHaveBeenCalledWith('settings', {
      ...storefrontData.entity.settings,
      base_config: {
        ...storefrontData.entity.settings.base_config,
        banner_feature_enabled: true,
      },
    });
    expect(mockHandleClick).toHaveBeenCalledWith(true);
  });

  it('renders alert and handles secondary button click', () => {
    render(
      <AddBannerDetails
        handleClick={mockHandleClick}
        openAddBannerDrawer={false}
        showBannerAlert={true}
        setShowBannerAlert={mockSetShowBannerAlert}
      />,
    );

    expect(screen.getByText(/Missing Banner Image/i)).toBeInTheDocument();

    const secondaryButton = screen.getByText('Disable Banner');
    fireEvent.click(secondaryButton);
    expect(mockSetShowBannerAlert).toHaveBeenCalled();

    const updateFunction = mockSetShowBannerAlert.mock.calls[0][0];

    const previousState = { showSocialHandleAlert: true, showBannerAlert: true };
    const result = updateFunction(previousState);
    expect(result).toEqual({
      showSocialHandleAlert: true,
      showBannerAlert: false,
    });

    expect(editStorefront).toHaveBeenCalledWith('settings', {
      ...storefrontData.entity.settings,
      base_config: {
        ...storefrontData.entity.settings.base_config,
        banner_feature_enabled: false,
      },
    });
  });
});
