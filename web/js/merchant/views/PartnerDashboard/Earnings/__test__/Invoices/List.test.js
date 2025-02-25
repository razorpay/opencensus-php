import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import CommissionInvoicesList from 'merchant/views/PartnerDashboard/Earnings/Invoices/List';
import {
  mockInvoicesFetchBulkRequestHandler,
  mockInvoicesFetchBulkEmpty,
} from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/once-handlers';
import {
  REQUEST_EPOCH_APRIL_2023,
  REQUEST_EPOCH_JANUARY_2023,
} from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/fixtures';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { getInitialUserOrgState } from 'common/tests/utils';

const defaultProps = {
  location: {
    search: '',
  },
};

describe('test suite for Invoices List', () => {
  beforeEach(() => {
    // set system time for fixed query params
    jest.setSystemTime(REQUEST_EPOCH_APRIL_2023);
  });

  const App = ({ initialState, ...props }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <CommissionInvoicesList {...props} />
      </Provider>
    );
  };

  test('should render empty list with curlec commission text', () => {
    mockInvoicesFetchBulkEmpty();
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<App initialState={{ session: state }} {...defaultProps} />);

    expect(
      screen.getByText(
        'Invoices are generated only if the monthly commission is greater than 1 MYR',
      ),
    ).toBeInTheDocument();
  });

  test('should render empty list with indian commission text', () => {
    mockInvoicesFetchBulkEmpty();
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<App initialState={{ session: state }} {...defaultProps} />);

    expect(
      screen.getByText(
        'Invoices are generated only if the monthly commission is greater than 1 Rupee',
      ),
    ).toBeInTheDocument();
  });

  test('should render non-empty list with indian amounts', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<App initialState={{ session: state }} {...defaultProps} />);
    await waitFor(() => {
      expect(screen.getByTestId('amount-LMdgZWLYAXpA11')).toHaveTextContent('₹ 6.00');
      expect(screen.getByText('02 Mar 2023')).toBeInTheDocument();
    });
  });

  test('should render non-empty list with curlec amounts', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<App initialState={{ session: state }} {...defaultProps} />);
    await waitFor(() => {
      expect(screen.getByTestId('amount-LMdgZWLYAXpA11')).toHaveTextContent('RM 6.00');
      expect(screen.getByText('02 Mar 2023')).toBeInTheDocument();
    });
  });

  test('should send correct params for current calendar year', (done) => {
    const state = getInitialUserOrgState({
      isRzpOrg: true,
      userExtra: { isShowInvoiceCurrentFY: true },
    });
    jest.setSystemTime(REQUEST_EPOCH_JANUARY_2023);

    mockInvoicesFetchBulkRequestHandler((req) => {
      const { searchParams } = req.url;
      try {
        expect(searchParams.get('from')).toBe('1714521600');
        done();
      } catch {
        done('Expectation failed');
      }
    });
    render(<App initialState={{ session: state }} {...defaultProps} />);
  });

  test('should send correct params for current financial year', (done) => {
    const state = getInitialUserOrgState({
      isRzpOrg: true,
      userExtra: { isShowInvoiceCurrentFY: true },
    });
    jest.setSystemTime(REQUEST_EPOCH_APRIL_2023);
    mockInvoicesFetchBulkRequestHandler((req) => {
      const { searchParams } = req.url;
      try {
        expect(searchParams.get('from')).toBe('1714521600');
        done();
      } catch {
        done('Expectation failed');
      }
    });
    render(<App initialState={{ session: state }} {...defaultProps} />);
  });
});
