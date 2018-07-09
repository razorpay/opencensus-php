import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { HashRouter as Router } from 'react-router-dom';

import 'rzp/utils/polyfills';
import store from 'merchant/store';

import ConfirmModalProvider from 'rzp/ui/ConfirmModal/ConfirmModalProvider';

import App from 'merchantLA/containers/App';

render(
  <Provider store={store}>
    <ConfirmModalProvider>
      <Router basename="/app">
        <App />
      </Router>
    </ConfirmModalProvider>
  </Provider>,
  document.getElementById('react-root')
);
