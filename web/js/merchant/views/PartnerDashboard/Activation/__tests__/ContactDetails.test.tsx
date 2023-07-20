import React from 'react';
import { render } from 'common/services/test/test-utils';
import ContactDetails from 'merchant/views/PartnerDashboard/Activation/Components/ContactDetails';

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

  test('Show Contact Details', () => {
    const props = {
      contactDetails: {
        contact_mobile: '9999988888',
      },
      isFormLocked: false,
      formState: {},
      onFormChange: () => {},
      commonLockedFields: [],
    };
    render(<ContactDetails {...props} />);
  });
});
