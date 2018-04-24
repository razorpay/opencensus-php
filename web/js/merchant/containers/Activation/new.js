import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { fetchActivationDetails } from 'merchant/modules/activation';
import { showNotification } from 'rzp/modules/notifications';
import { without } from 'rzp/utils/rzp-utils';
import * as ActivationActions from 'merchant/modules/activation';

import Spinner from 'rzp/ui/Spinner';
import ActivationWizard from 'component/merchant/Activation';

@connect(state => state.activation, {
  fetchActivationDetails,
  showNotification,
  ...ActivationActions,
})
export default class ActivationContainer extends React.Component {
  componentWillMount() {
    this.props.fetchActivationDetails();
  }

  _save = (props, accountId) => {
    let data = without(props, [
      'or_same',
      'bank_account_number_confirmation',
      'steps_finished',
    ]);

    if (false) {
      // Request for Submit form
      return this.props.submitForm({ data, accountId }).then(() => {
        return this.props.fetchActivationDetails(accountId);
      });
    } else {
      return this.props.saveStep({ data, accountId });
    }
  };

  saveStep = (props, accountId) => {
    return this._save(props, accountId).catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });

      throw err;
    });
  };

  render() {
    let { loading, data } = this.props;

    return (
      <div>
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <ActivationWizard data={data} save={this.saveStep} />
        )}
      </div>
    );
  }
}
