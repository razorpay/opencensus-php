import { Provider } from 'react-redux';
import store from 'merchant/store';
import ViewV3 from './V3/View';

export default class FormView extends React.PureComponent {
  render() {
    return (
      <Provider store={store}>
        <ViewV3 />
      </Provider>
    );
  }
}
