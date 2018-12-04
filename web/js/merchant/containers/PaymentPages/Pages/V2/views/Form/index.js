import { Provider } from 'react-redux';
import store from 'merchant/store';
import View from './View';

export default class FormView extends React.PureComponent {
  render() {
    return (
      <Provider store={store}>
        <View />
      </Provider>
    );
  }
}
