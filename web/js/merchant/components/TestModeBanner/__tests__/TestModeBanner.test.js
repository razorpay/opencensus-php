import { Provider } from 'react-redux';
import { render, screen, fireEvent } from 'test-utils';

import { storeWithInitialState } from 'merchant/store';
import '@testing-library/jest-dom/extend-expect';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { isOrgFeatureExist } from 'merchant/models/User';

const props = {
  user: {
    current: 'K0e9Jske9Kde',
    merchants: [
      { id: 1, product: 'primary', name: 'Test1' },
      { id: 2, product: 'banking', name: 'Test2' },
    ],
    isActivated: false,
  },
  isNcEligibile: false,
  mode: 'test',
};
const testModeText = 'Test Mode';
const liveModeText = 'Live mode';

jest.mock('merchant/models/User', () => ({
  __esModule: true,
  isOrgFeatureExist: jest.fn().mockReturnValue(false),
}));

describe('TestModeBanner', () => {
  beforeEach(() => {
    window.rzp_user = { isActivated: false };
  });
  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <TestModeBanner {...rest} />
      </Provider>
    );
  };

  it('should render the component', () => {
    render(<App {...props} />);
    expect(screen.getByText(testModeText)).toBeInTheDocument();
  });

  it('should not return JSX if mode is live', () => {
    render(<App initialState={{ session: { mode: 'live' } }} />);
    expect(screen.queryByText(testModeText)).not.toBeInTheDocument();
  });

  it('should not return JSX if isOrgAxis', () => {
    render(<App initialState={{ session: { user: { ...props.user, isOrgAxis: true } } }} />);
    expect(screen.queryByText(testModeText)).not.toBeInTheDocument();
  });

  it('should not return JSX if hide_activation_form org FF exists', () => {
    isOrgFeatureExist.mockReturnValueOnce(true);
    render(<App {...props} />);
    expect(screen.queryByText(testModeText)).not.toBeInTheDocument();
  });

  it('should click on switch to live mode', () => {
    render(<App initialState={{ session: { user: { ...props.user, isActivated: true } } }} />);
    const liveMode = screen.getByText(liveModeText);
    fireEvent.click(liveMode);
    expect(screen.getByText(liveModeText)).toBeInTheDocument();
  });
});
