import { Provider } from 'react-redux';
import store from 'merchant/store';
import ViewV2 from './V2/View';
import ViewV3 from './V3/View';

export default class FormView extends React.PureComponent {
  render() {
    return (
      <Provider store={store}>
        {store.getState().session.user.isPPV3Enabled ? <ViewV3 /> : <ViewV2 />}
      </Provider>
    );
  }
}
