import React from 'react';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import { useFormContext, useController } from 'react-hook-form';
import DeviceFee from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DeviceSelectionCatalog/DeviceFee';
import { DeviceFeeTypes } from 'apps/pos/src/app/constants/DeviceSelection';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';

jest.mock('react-hook-form', () => ({
  ...jest.requireActual('react-router-dom'),
  useFormContext: jest.fn(),
  useController: jest.fn(),
}));

jest.mock('apps/pos/src/services/analytics', () => ({
  trackEvent: jest.fn(),
}));

const mockUseFormContext = useFormContext as jest.Mock;
const mockUseController = useController as jest.Mock;

const renderComponent = (props: any) => {
  render(<DeviceFee {...props} />);
};

describe('DeviceFee', () => {
  const mockDeviceFee = {
    field: MODULAR_DEVICE_FIELDS.DEVICE_SETUP_FEE_TYPE,
    customAmountField: MODULAR_DEVICE_FIELDS.DEVICE_SETUP_CUSTOM_FEE_AMOUNT,
    title: 'Device Fee Title',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseFormContext.mockReturnValue({
      control: {},
      setValue: jest.fn(),
      watch: jest.fn().mockReturnValue('monthly'),
    });
    mockUseController.mockImplementation(({ name }) => {
      if (name === mockDeviceFee.field) {
        return {
          field: {
            value: 'standard',
            onChange: jest.fn(),
          },
        };
      }
      if (name === mockDeviceFee.customAmountField) {
        return {
          field: {
            name: 'customAmountField',
            value: '100',
            onChange: jest.fn(),
          },
          fieldState: {
            error: null,
          },
        };
      }
    });
  });

  test('should render DeviceFee with correct title', () => {
    renderComponent({ deviceFee: mockDeviceFee });
    expect(screen.getByText('Device Fee Title')).toBeInTheDocument();
  });

  test('should change radio button value', () => {
    renderComponent({ deviceFee: mockDeviceFee });
    const radio = screen.getByLabelText(DeviceFeeTypes[0].name);
    userEvent.click(radio);
    expect(radio).toBeChecked();
  });

  test('should enter custom amount', async () => {
    renderComponent({ deviceFee: mockDeviceFee });
    const customInput = screen.getByRole('textbox');
    await userEvent.type(customInput, '100');
    expect(customInput).toHaveValue('100');
  });
});
