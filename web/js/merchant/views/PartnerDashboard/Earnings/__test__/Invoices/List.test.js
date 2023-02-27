import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import CommissionInvoicesList from 'merchant/views/PartnerDashboard/Earnings/Invoices/List';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

function getInitialState(isRzpOrg) {
  return {
    user: {
      merchant: {
        currency: isRzpOrg ? 'INR' : 'MYR',
      },
    },
    org: {
      custom_code: isRzpOrg ? 'rzp' : 'curlec',
    },
  };
}

const defaultProps = {
  location: {
    search: '',
  },
};

describe('test suite for Invoices List', () => {
  const App = ({ initialState, ...props }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <CommissionInvoicesList {...props} />
      </Provider>
    );
  };

  test('should render component with curlec commission text', () => {
    const state = getInitialState(false);
    render(<App initialState={{ session: state }} {...defaultProps} />);

    expect(
      screen.getByText(
        'Invoices are generated only if the monthly commission is greater than 1 MYR',
      ),
    ).toBeInTheDocument();
  });

  test('should render component with indian commission text', () => {
    const state = getInitialState(true);
    render(<App initialState={{ session: state }} {...defaultProps} />);

    expect(
      screen.getByText(
        'Invoices are generated only if the monthly commission is greater than 1 Rupee',
      ),
    ).toBeInTheDocument();
  });
});
