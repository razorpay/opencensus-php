import React from 'react';
import { fireEvent, render, screen, userEvent } from 'common/services/test/test-utils';
import ActivationWizard from 'merchant/components/Activation';
import store from 'merchant/store';

describe('<ActivationWizard /> ', () => {
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

  test.skip('Validate Business Website field does not contain "razorpay" when field is present', async () => {
    const props = {
      user: {
        partner_type: 'aggregator',
        instantActivation: { isL1Submitted: false },
        user: { settings: '' },
      },
      store,
      data: { has_url: '1', app_website_url: '1', business_website: '' },
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

    expect(screen.getByText('On my website/app')).toBeInTheDocument();
    const radioOption = screen.getByText('On my website/app');
    await userEvent.click(radioOption);

    // expect(screen.getByRole('textbox', { name: 'playstore_url' })).toBeInTheDocument();
    const checkbox = screen.getByText('Accept payments on Website');
    await userEvent.click(checkbox);

    const websiteField = screen.getByPlaceholderText('Enter URL');
    expect(websiteField).toBeInTheDocument();

    fireEvent.change(websiteField, { target: { value: 'https://razorpay.com' } });
    fireEvent.blur(websiteField);

    const validationError = screen.queryByText('Invalid website URL');
    expect(validationError).toBeInTheDocument();
  });

  test.skip('Validate Playstore URL field with an invalid and valid URL', async () => {
    const props = {
      user: {
        partner_type: 'aggregator',
        instantActivation: { isL1Submitted: false },
        user: { settings: '' },
      },
      store,
      data: { has_url: '1', app_website_url: '1', business_website: '' },
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

    expect(screen.getByText('On my website/app')).toBeInTheDocument();
    const radioOption = screen.getByText('On my website/app');
    await userEvent.click(radioOption);

    expect(screen.getByText('Accept payments on Website')).toBeInTheDocument();
    const checkbox = screen.getByText('Accept payments on app');
    await userEvent?.click(checkbox);

    const playstoreField = screen.getByRole('textbox', { name: 'playstore_url' });
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
