import MagicSettings from '..';
import { screen } from 'test-utils';
import { render as reactRender } from '@testing-library/react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { Router } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import { waitFor } from '@testing-library/dom';

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState(state)}>
      <MagicSettings {...props} />
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

describe('magic settings', () => {
  test('render magic settings', async () => {
    reactRender(<AppWithRouter />);
    await waitFor(() => {
      expect(screen.getByText(/^Platform?/i)).toBeInTheDocument();
    });
  });
});
