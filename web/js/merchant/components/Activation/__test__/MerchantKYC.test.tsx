import React from 'react';
import { fireEvent, render, screen, userEvent } from 'common/services/test/test-utils';
import ActivationWizard from 'merchant/components/Activation';
import store from 'merchant/store';

describe('<ActivationWizard /> ', () => {
  beforeEach(() => {
    window.rzpQ = {
      onbr: () => ({ success: jest.fn(), failed: jest.fn() }),
      component: () => true,
    };
    window.rzp_user = {
      email: 'test@gmail.com',
    };
  });

  test('Check Email Verification for Partners', () => {
    const props = {
      user: {
        partner_type: 'aggregator',
        instantActivation: { isL1Submitted: false },
        user: { settings: '' },
      },
      store,
      data: {},
      categories: {},
      clarificationReasons: '',
      saveFile: () => {},
      trackEventsAction: () => {},
      submerchantId: '123',
      updateUser: () => {},
    };
    render(<ActivationWizard {...props} />);
    expect(screen.getByText('Contact Email')).toBeInTheDocument();
  });

  test('Check Email Verification not present if not a partner', () => {
    const props = {
      user: {
        partner_type: null,
        instantActivation: { isL1Submitted: false },
        user: { settings: '', confirmed: false },
      },
      store,
      data: {},
      categories: {},
      clarificationReasons: '',
      saveFile: () => {},
      trackEventsAction: () => {},
      submerchantId: '123',
      updateUser: () => {},
    };
    render(<ActivationWizard {...props} />);
    expect(screen.getByText('Contact Number')).toBeInTheDocument();
  });

  test('Validate Business Website field does not contain "razorpay" when field is present', async () => {
    const props = {
      user: {
        partner_type: 'aggregator',
        instantActivation: { isL1Submitted: false },
        user: { settings: '' },
      },
      store,
      data: { has_url: '1', app_website_url: '1' },
      categories: {},
      clarificationReasons: '',
      saveFile: () => {},
      trackEventsAction: () => {},
      submerchantId: '123',
      updateUser: () => {},
    };

    render(<ActivationWizard {...props} />);

    expect(screen.getByText('Business Overview')).toBeInTheDocument();
    const businessOverviewTab = screen.getByText('Business Overview');
    await userEvent.click(businessOverviewTab);

    const websiteField = screen.getByTestId('business_webiste_url_ip');
    expect(websiteField).toBeInTheDocument();

    fireEvent.change(websiteField, { target: { value: 'https://razorpay.com' } });
    fireEvent.blur(websiteField);

    const validationError = screen.queryByText('Invalid website URL');
    expect(validationError).toBeInTheDocument();
  });

  test('Validate Playstore URL field with an invalid and valid URL', async () => {
    const props = {
      user: {
        partner_type: 'aggregator',
        instantActivation: { isL1Submitted: false },
        user: { settings: '' },
      },
      store,
      data: { has_url: '1', app_website_url: '1', app_url: '1', business_website: '' },
      categories: {},
      clarificationReasons: '',
      saveFile: () => {},
      trackEventsAction: () => {},
      submerchantId: '123',
      updateUser: () => {},
    };

    render(<ActivationWizard {...props} />);

    expect(screen.getByText('Business Overview')).toBeInTheDocument();
    const businessOverviewTab = screen.getByText('Business Overview');
    await userEvent.click(businessOverviewTab);

    const playstoreField = screen.getByTestId('playstore_url');
    expect(playstoreField).toBeInTheDocument();

    fireEvent.change(playstoreField, { target: { value: 'https://invalid-url.com' } });
    fireEvent.blur(playstoreField);

    const validationError = screen.queryByText('Please enter valid Playstore link');
    expect(validationError).toBeInTheDocument();

    fireEvent.change(playstoreField, {
      target: { value: 'https://play.google.com/store/apps/details?id=com.example.app' },
    });
    fireEvent.blur(playstoreField);

    expect(screen.queryByText('Please enter valid Playstore link')).not.toBeInTheDocument();
  });
});
