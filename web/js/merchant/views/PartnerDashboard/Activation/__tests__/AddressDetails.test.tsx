import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import AddressDetails, {
  validateBothAddressSame,
} from 'merchant/views/PartnerDashboard/Activation/Components/AddressDetails';

describe('<Activation /> ', () => {
  beforeAll(() => {
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });

  test('Show Address Details', () => {
    const props = {
      addressDetails: {},
      isFormLocked: false,
      formState: {},
      onFormChange: () => {},
      autoFillFromPinCode: () => {},
      commonLockedFields: [],
    };
    render(<AddressDetails {...props} />);
    expect(screen.getByText('Registered Business Address')).toBeInTheDocument();
  });

  test('Validate Both Addresses', () => {
    const addressDetails = {
      business_registered_address: 'abc',
      business_registered_pin: 122003,
      business_registered_city: 'Gurgaon',
      business_registered_state: 'HA',
      business_operation_address: 'abc',
      business_operation_pin: 122003,
      business_operation_city: 'Gurgaon',
      business_operation_state: 'HA',
    };

    expect(validateBothAddressSame(addressDetails)).toEqual(true);
  });
});
