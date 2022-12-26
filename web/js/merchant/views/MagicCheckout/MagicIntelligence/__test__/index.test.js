import MagicIntelligence from 'merchant/views/MagicCheckout/MagicIntelligence';
import { fireEvent, screen } from 'test-utils';
import { Provider } from 'react-redux';
import { render } from '@testing-library/react';
import { storeWithInitialState } from 'merchant/store';
import { Router } from 'react-router-dom';
import { createMemoryHistory } from 'history';

const App = ({ state = {}, ...props }) => {
  return (
    <Provider
      store={storeWithInitialState({
        ...state,
      })}
    >
      <MagicIntelligence {...props} />
    </Provider>
  );
};

const AppWithRouter = ({ ...props }) => {
  return (
    <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
      <App {...props} />
    </Router>
  );
};

describe('Magic Intelligence component', () => {
  test('should show routes in the container', () => {
    render(<App />);
    expect(screen.queryByTestId('magic-intelligence-tabbed-container')).toBeInTheDocument();
  });

  test('should render different component on another tab click', () => {
    render(<AppWithRouter />);
    const blocklist = screen.getByText(/Blocklist/i);
    expect(blocklist).toBeInTheDocument();
    fireEvent.click(blocklist);
  });
});
