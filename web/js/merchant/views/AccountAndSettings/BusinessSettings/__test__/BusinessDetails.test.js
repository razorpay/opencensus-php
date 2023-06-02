import BusinessDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/BusinessDetails';
import { render, screen } from 'test-utils';
import { BUSINESS_TYPE_MAP } from 'merchant/views/Account/constants';
import { titleCase } from 'common/utils/rzp-utils';
import { testNewStylesUsingFlowRevamped } from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';

const user = {
  business_name: 'ACME Business',
  business_type: 5,
  created_at: 1672531201,
  marketplace_merchant_name: 'Test Merchant',
};

const renderApp = ({ isFlowRevamped } = {}) => {
  render(<BusinessDetails isFlowRevamped={isFlowRevamped} />, {
    initialState: {
      session: {
        user,
        org: {},
      },
    },
  });
};

describe('BusinessDetails', () => {
  test('should render business details', () => {
    renderApp();

    expect(screen.getByText('Business Name')).toBeInTheDocument();
    // text is converted to title case
    expect(screen.queryByText(user.business_name)).not.toBeInTheDocument();
    expect(screen.getByText(titleCase(user.business_name))).toBeInTheDocument();

    expect(screen.getByText('Business Type')).toBeInTheDocument();
    expect(screen.getByText(BUSINESS_TYPE_MAP[user.business_type])).toBeInTheDocument();

    expect(screen.getByText('Registration Date')).toBeInTheDocument();
    expect(screen.getByText('Jan 01 2023, 12:00:01 am')).toBeInTheDocument();

    expect(screen.getByText('Registered By')).toBeInTheDocument();
    expect(screen.getByText(user.marketplace_merchant_name)).toBeInTheDocument();
  });

  testNewStylesUsingFlowRevamped(renderApp, 'business-details-section');
});
