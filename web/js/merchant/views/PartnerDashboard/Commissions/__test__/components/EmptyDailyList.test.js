import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import EmptyDailyList from 'merchant/views/PartnerDashboard/Commissions/components/EmptyDailyList';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { getInitialUserOrgState } from 'common/tests/utils';

function getInitialProps(itemList) {
  return {
    isAddMerchantView: true,
    items: itemList,
  };
}

describe('test suite for Empty Daily List', () => {
  const App = ({ initialState, ...props }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <EmptyDailyList {...props} />
      </Provider>
    );
  };

  test('should render component with default rzp limit', () => {
    const props = getInitialProps([]);
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<App initialState={{ session: state }} {...props} />);

    expect(
      screen.getByText('Add more accounts (>3) to unlock the details view of processed earnings'),
    ).toBeInTheDocument();
  });

  test('should render component without limit text', () => {
    const props = getInitialProps([]);
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<App initialState={{ session: state }} {...props} />);

    expect(
      screen.getByText(/Add more accounts to unlock the details view of processed earnings/i),
    ).toBeInTheDocument();
  });

  test('should render component with mentioned limit text', () => {
    const props = getInitialProps({ limit: 2 });
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<App initialState={{ session: state }} {...props} />);

    expect(
      screen.getByText('Add more accounts (>2) to unlock the details view of processed earnings'),
    ).toBeInTheDocument();
  });
});
