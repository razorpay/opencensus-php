import React from 'react';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import DeliveryInfo from 'merchant/views/POS/ProductDescription/DeliveryInfo';
import { MOCK_GTM } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getPincodeInfoHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import * as posServices from 'merchant/views/POS/services';
import { render, screen, server, userEvent, waitFor } from 'test-utils';

jest.spyOn(analytics, 'track_EXPERIMENTAL');

jest.mock('common/splitz', () => ({
  ...(jest.requireActual('common/splitz') as Record<string, string>),
  useSplitzService: () => ({
    abExperiments: MOCK_GTM,
  }),
}));

describe('<DeliveryInfo/>', () => {
  test('should render delivery info on screen', () => {
    render(<DeliveryInfo productTitle="" />);
    expect(screen.getByText('Delivery Info')).toBeVisible();
  });

  test('should trigger getPinCode info with correct params', async () => {
    const getPinCodeSpy = jest.spyOn(posServices, 'getPincodeInfo');
    render(<DeliveryInfo productTitle="" />);
    await userEvent.type(screen.getByPlaceholderText('Enter PIN Code'), '560034');
    expect(screen.getByPlaceholderText('Enter PIN Code')).toHaveValue('560034');
    await userEvent.click(screen.getByText('Check'));
    expect(getPinCodeSpy).toHaveBeenCalledWith('560034');
  });

  test('should clear field if clear event triggered', async () => {
    render(<DeliveryInfo productTitle="" />);
    await userEvent.type(screen.getByPlaceholderText('Enter PIN Code'), '560034');
    expect(screen.getByPlaceholderText('Enter PIN Code')).toHaveValue('560034');
    await userEvent.clear(screen.getByPlaceholderText('Enter PIN Code'));
    expect(screen.getByPlaceholderText('Enter PIN Code')).toHaveValue('');
  });

  test('should show correct success message if delivery available', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_available' }));
    render(<DeliveryInfo productTitle="" />);
    await userEvent.type(screen.getByPlaceholderText('Enter PIN Code'), '560034');
    await userEvent.click(screen.getByText('Check'));
    await waitFor(() => {
      expect(screen.getByText('Delivery in 2-3 business days post KYC approval.')).toBeVisible();
    });
  });

  test('should show correct error message if delivery unavailable', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_unavailable' }));
    render(<DeliveryInfo productTitle="" />);
    await userEvent.type(screen.getByPlaceholderText('Enter PIN Code'), '781019');
    await userEvent.click(screen.getByText('Check'));
    await waitFor(() => {
      expect(screen.getByText('Pincode not serviceable! Arriving Soon.')).toBeVisible();
    });
  });

  test('should show correct error message if invalid pincode entered', async () => {
    server.use(getPincodeInfoHandler({ type: 'pincode_invalid' }));
    render(<DeliveryInfo productTitle="" />);
    await userEvent.type(screen.getByPlaceholderText('Enter PIN Code'), '781019');
    await userEvent.click(screen.getByText('Check'));
    await waitFor(() => {
      expect(screen.getByText('Pincode entered is not valid')).toBeVisible();
    });
  });

  test('should track form field initiated', async () => {
    render(<DeliveryInfo productTitle="" />);
    await userEvent.type(screen.getByPlaceholderText('Enter PIN Code'), '560034');
    // trigger tab to fire onBlur event
    await userEvent.tab();
    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(
        SignUpEvents.formFieldFillInitiated,
        {
          formName: 'Delivery Availability Check',
          fieldName: 'Pincode',
          fieldType: 'Text Box',
          section: 'Device',
          subSection: '',
          l1FunnelStage: 'Device Exploration',
          l2FunnelStage: 'Delivery Availability Check',
        },
      );
    });

    await userEvent.type(screen.getByPlaceholderText('Enter PIN Code'), '560048');
    await userEvent.tab();

    // track function will only be called once
    expect(analytics.track_EXPERIMENTAL).toBeCalledTimes(1);
  });
});
