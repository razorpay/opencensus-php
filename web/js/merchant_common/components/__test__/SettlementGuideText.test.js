import SettlementGuideText from 'merchant_common/components/SettlementGuideText';
import User from 'merchant/models/User';
import store from 'merchant/store';
import { render, screen, updateUseI18ServiceSpy } from 'test-utils';

const defaultProps = {};

const updateStore = (user, org = {}) => {
  const updatedStore = store.getState();
  updatedStore.session.user = new User({ ...updatedStore.session.user, ...user });
  updatedStore.session.org = { ...updatedStore.session.org, ...org };
  return updatedStore;
};

const renderApp = ({ props, state }) =>
  render(<SettlementGuideText {...props} />, { initialState: state });

describe('test for SettlementGuideText component', () => {
  test('should hide component if settlements.settlement_guide is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore(
      {
        merchant: { country_code: 'MY' },
      },
      { features: [] },
    );
    updateUseI18ServiceSpy('settlements.settlement_guide');
    renderApp({ props, updatedState });
    expect(screen.queryByText('See our Settlements Guide')).not.toBeInTheDocument();
  });
});
