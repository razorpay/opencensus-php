import React from 'react';
import { render } from 'common/services/test/test-utils';
import BusinessDetails from 'merchant/views/PartnerDashboard/Activation/Components/BusinessDetails';

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

  test('Show Business Details', () => {
    const props = {
      businessDetails: {},
      isFormLocked: false,
      formState: {},
      onFormChange: () => {},
      commonLockedFields: [],
    };
    render(<BusinessDetails {...props} />);
  });
});
