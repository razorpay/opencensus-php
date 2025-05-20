import React from 'react';
import { render, screen } from 'apps/pos/src/services/test/test-utils';
import {
  DeviceCard,
  DeviceCardProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/DevicesList/components/DeviceCard';

const renderApp = (props: DeviceCardProps) => {
  render(<DeviceCard {...props} />);
};

describe('Test POS payment link method screen', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });
  const props: DeviceCardProps = {
    imageUrl: 'https://razorpay.com/razorpay-logo.png',
    name: 'A90',
    isDeployed: false,
    type: 'allDevices',
    plan: 'monthly',
    deviceModelLabel: 'Android Lite',
    serialNumber: '38293232',
    deviceId: 'A893490',
    isModularLoading: false,
    vpa: 'VPA67',
    currentDeployingDeviceId: 'WD10',
    isKycQualified: true,
    handleDeployNow: () => {},
    handleDetailsClick: () => {},
  };

  test('should render device card with all data', async () => {
    renderApp(props);
    expect(
      screen.getByRole('button', {
        name: /deploy now/i,
      }),
    ).toBeInTheDocument();
    expect(screen.getByText(/A90/i)).toBeInTheDocument();
    expect(screen.getByText(/Android Lite/i)).toBeInTheDocument();
    expect(screen.getByText(/38293232/i)).toBeInTheDocument();
    expect(screen.getByText(/VPA67/i)).toBeInTheDocument();
    expect(screen.getByText(/monthly/i)).toBeInTheDocument();
    expect(
      screen.getByRole('img', {
        name: /a90/i,
      }),
    ).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /details/i })).not.toBeInTheDocument();
  });

  test('should render device card with all data and deployed details link', async () => {
    renderApp({...props,type: 'deployedDevices'});
    expect(
      screen.queryByRole('button', {
        name: /deploy now/i,
      }),
    ).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: /details/i })).toBeInTheDocument();
  });

  test('should render device card with all data and deployed details link', async () => {
    renderApp({...props,isDeployed: true});
    expect(
      screen.queryByRole('button', {
        name: /deploy now/i,
      }),
    ).not.toBeInTheDocument();
    expect(screen.getByText(/deployed/i)).toBeInTheDocument();
  });

  test('should render null if deviceModelLabel is absent', async () => {
    renderApp({
      imageUrl: 'https://razorpay.com/razorpay-logo.png',
      name: 'A90',
      isDeployed: false,
      type: 'allDevices',
      plan: 'monthly',
      serialNumber: '38293232',
      deviceId: 'A893490',
      isModularLoading: false,
      vpa: 'VPA67',
      currentDeployingDeviceId: 'WD10',
      isKycQualified: true,
      handleDeployNow: () => {},
      handleDetailsClick: () => {},
    });
    expect(
      screen.queryByText(/Android lite/i)
    ).not.toBeInTheDocument();
  });
});
