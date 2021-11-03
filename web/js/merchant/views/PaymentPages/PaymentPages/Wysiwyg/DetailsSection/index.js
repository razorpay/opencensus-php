import React from 'react';
import { Provider } from 'react-redux';
import store from 'merchant/store';
import View from './View';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';

export default class DetailsView extends React.PureComponent {
  render() {
    return (
      <Provider store={store}>
        <ConfirmModalProvider>
          <View {...this.props} />
        </ConfirmModalProvider>
      </Provider>
    );
  }
}
