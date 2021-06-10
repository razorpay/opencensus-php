import { Provider } from 'react-redux';
import store from 'merchant/store';
import View from './View';

export default class DetailsView extends React.PureComponent {
  render() {
    return (
      <Provider store={store}>
        <View {...this.props} />
      </Provider>
    );
  }
}
