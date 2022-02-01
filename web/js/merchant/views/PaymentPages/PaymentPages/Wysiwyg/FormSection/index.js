import React from 'react';
import { Provider } from 'react-redux';
import store from 'merchant/store';
import ViewV3 from './View';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';

export default class FormView extends React.PureComponent {
  render() {
    return (
      <Provider store={store}>
        <ConfirmModalProvider>
          <ViewV3 />
        </ConfirmModalProvider>
      </Provider>
    );
  }
}
