import React from 'react';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import { DevicesList } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/DevicesList/DevicesList';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

jest.mock('apps/pos/src/services/analytics', () => ({
  analyticsTypes: {
    ANALYTICS_EVENTS: {
      WEBSITE_CTA: 'WEBSITE_CTA',
    },
    ANALYTICS_ACTIONS: {
      CLICKED: 'CLICKED',
    },
    L1_FUNNEL_STAGE: {
      DEVICE_DEPLOYMENT: 'DEVICE_DEPLOYMENT',
    },
    L2_FUNNEL_STAGE: {
      EXPLORE_DEVICE_DEPLOYMENT: 'EXPLORE_DEVICE_DEPLOYMENT',
    },
  },
  trackEvent: jest.fn(),
}));

const mockDevices = [
  {
    id: '1',
    device_serial: 'DEV001',
    display_name: 'Test Device 1',
    mapped_vpa: 'test@upi',
    mapping_status: 'deployed',
    plan_name: 'Basic',
    display_label: 'Label 1',
    icon: 'test-icon-1',
    device_model: 'model1',
    device_order_item_id: 'order1',
    setup_charge: 100,
  },
  {
    id: '2',
    device_serial: 'DEV002',
    display_name: 'Test Device 2',
    mapped_vpa: '',
    mapping_status: 'inactive',
    plan_name: 'Pro',
    display_label: 'Label 2',
    icon: 'test-icon-2',
    device_model: 'model2',
    device_order_item_id: 'order2',
    setup_charge: 200,
  },
];

const defaultProps = {
  title: 'Test Devices',
  type: 'allDevices' as const,
  devices: mockDevices,
  isModularLoading: false,
  handleDeployNow: jest.fn(),
  handleDetailsClick: jest.fn(),
  isKycQualified: true,
};
const mockNavigate = jest.fn();
const mockParams = { id: 'test-id' };

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
  useParams: () => mockParams,
}));

const renderComponent = (props = {}) => {
  return render(<DevicesList {...defaultProps} {...props} />);
};

describe('DevicesList', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders component with title', () => {
    renderComponent();
    expect(screen.getByText('Test Devices')).toBeInTheDocument();
  });

  test('filters devices by search query', async () => {
    renderComponent();
    const searchInput = screen.getByPlaceholderText('Device Name/ Serial Number');

    await userEvent.type(searchInput, 'Test Device 1');
    expect(screen.getByText('Test Device 1')).toBeInTheDocument();
    expect(screen.queryByText('Test Device 2')).not.toBeInTheDocument();
  });

  test('filters devices by status', async () => {
    renderComponent();

    const deployedChip = screen.getByText('Deployed');
    await userEvent.click(deployedChip);

    expect(screen.getByText('Test Device 1')).toBeInTheDocument();
    expect(screen.queryByText('Test Device 2')).not.toBeInTheDocument();
  });

  test('shows back to dashboard button when all devices are deployed', () => {
    renderComponent({ isAllDevicesDeployed: true });
    expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
  });

  test('triggers analytics on back to dashboard click', async () => {
    renderComponent({ isAllDevicesDeployed: true });
    const backButton = screen.getByText('Back to Dashboard');
    await userEvent.click(backButton);
    expect(trackEvent).toHaveBeenCalledWith(
      expect.objectContaining({
        properties: expect.objectContaining({
          label: 'Back to Dashboard',
          subSection: 'Devices Deployed',
        }),
      }),
    );
  });

  test('calls handleDeployNow when deploy button is clicked', async () => {
    renderComponent();
    const deployButton = screen.getAllByRole('button', { name: 'Deploy Now' });
    await userEvent.click(deployButton[0]);

    expect(defaultProps.handleDeployNow).toHaveBeenCalled();
  });

  test('shows correct number of device cards', () => {
    renderComponent();
    const deviceCards = screen.getAllByTestId('device-card');
    expect(deviceCards.length).toBe(2);
  });
});
