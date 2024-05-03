import { render } from 'test-utils';

import store from 'merchant/store';
import CongfigurationContainer from 'merchant/views/Settings/Configuration/index';

const globalState = store.getState();

const defaultProps = {};

const renderApp = (initialState = {}, props = {}, showModal = false) => {
  render(<CongfigurationContainer {...props} />, {
    showModal,
    initialState: {
      ...globalState,
      session: {
        ...globalState.session,
        user: {
          ...(initialState?.session?.user ?? globalState?.session?.user),
        },
        org: initialState?.session?.org ?? globalState?.session?.org,
      },
    },
  });
};

export { renderApp, defaultProps };
