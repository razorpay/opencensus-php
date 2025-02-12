import React from 'react';
import moment from 'moment';
import { Merchant } from '@dashboard/shared-utils/graphql/graph-types';
import DeviceDeliveryAddress from '../DeviceDeliveryAddress';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import {
  TestAddedDeviceWithDeviceConfig,
  TestDeviceOrderSummary,
} from 'apps/pos/src/services/mocks/fixtures/deviceSelection';
import { MOCK_MERCHANT_DETAILS } from 'apps/pos/src/services/mocks/fixtures/merchantDetails';

const defaultProps = {
  title: 'Device delivery address',
  countryCode: 'IN',
  addedDevices: [TestAddedDeviceWithDeviceConfig],
  orderSummary: TestDeviceOrderSummary,
  merchantDetails: MOCK_MERCHANT_DETAILS as unknown as Merchant,
  handleModularUpdate: jest.fn(),
  handleGoToNextStep: jest.fn(),
  isUpdateModularLoading: false,
  isStepCompleted: false,
};

const renderApp = (props = {}) => {
  const initProps = {
    ...defaultProps,
    ...props,
  };
  render(<DeviceDeliveryAddress {...initProps} />);
};

describe('DeviceDeliveryAddress', () => {
  test('should render device delivery address selection component on screen', () => {
    renderApp();
    expect(screen.getByText('Device delivery address')).toBeInTheDocument();
    expect(screen.getByText('Registered Address')).toBeInTheDocument();
    expect(screen.getByText('Operational Address')).toBeInTheDocument();
    expect(screen.getAllByText('Test Merchant').length).toBe(2);
    expect(screen.getAllByText('1234567890').length).toBe(2);
    expect(screen.getAllByText('32, 1st ave').length).toBe(2);
  });

  test('should order confirmation with confirmation warning', () => {
    renderApp();
    expect(screen.getByText('Are you sure about your order?')).toBeInTheDocument();
    expect(screen.getByText('2,478.00')).toBeInTheDocument();
    expect(screen.getByText('View Details')).toBeInTheDocument();
  });

  test('should call update modular with correct payload on confirmation', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Confirm Delivery Address'));
    expect(defaultProps.handleModularUpdate).toHaveBeenCalledWith({
      device_delivery_address_field: {
        city: 'Bengaluru',
        contact: '1234567890',
        country: 'IN',
        landmark: '',
        line1: '32, 1st ave',
        line2: '420',
        name: 'Test Merchant',
        state: 'Karnataka',
        zipcode: '560034',
      },
      modular_callback: expect.any(Function),
      qr_payment_amount_field: 2478,
      check_for_order_completion: moment().unix(),
      check_for_payment_exp_field: moment().unix(),
    });
  });

  test('should show error screen if merchant KYC not done yet', () => {
    renderApp({
      merchantDetails: {
        ...MOCK_MERCHANT_DETAILS,
        activation: { ...MOCK_MERCHANT_DETAILS.activation, isFormSubmitted: false },
        business: {
          ...MOCK_MERCHANT_DETAILS.business,
          address: {
            ...MOCK_MERCHANT_DETAILS.business.address,
            registered: {
              city: {
                value: null,
              },
              country: {
                value: null,
              },
              district: {
                value: null,
              },
              line1: {
                value: null,
              },
              line2: {
                value: null,
              },
              state: {
                value: null,
              },
              zipCode: {
                value: null,
              },
            },
          },
        },
      },
    });
    expect(
      screen.getByText('Please fill business address in KYC journey first and try again'),
    ).toBeInTheDocument();
  });
});
