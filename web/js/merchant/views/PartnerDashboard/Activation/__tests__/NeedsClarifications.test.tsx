import React from 'react';
import { render } from 'common/services/test/test-utils';
import NeedsClarifications from 'merchant/views/PartnerDashboard/Activation/Components/NeedsClarifications';

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

  test('Show Needs Clarifications', () => {
    const props = {
      data: {},
      formState: {},
      NCFields: {},
      commentlist: [],
      setCommentlist: () => {},
      user: {},
      ncFormResponse: {},
      setNCFormResponse: () => {},
    };
    render(<NeedsClarifications {...props} />);
  });
});
