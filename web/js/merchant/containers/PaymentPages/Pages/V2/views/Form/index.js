import { Provider } from 'react-redux';
import { render } from 'react-dom';

import ConfirmModalProvider from 'rzp/ui/ConfirmModal/ConfirmModalProvider';
import store from 'merchant/store';

export default class FormView extends React.PureComponent {
  render() {
    return (
      <Provider store={store}>
        <ConfirmModalProvider>
          <View />
        </ConfirmModalProvider>
      </Provider>
    );
  }
}

class View extends React.PureComponent {
  componentDidMount() {
    console.log('render form..');
  }

  render() {
    return <div>Hello World</div>;
  }
}
