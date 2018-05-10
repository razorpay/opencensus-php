import { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import { fetchActivationDetails } from 'merchant/modules/activation';
import ActivationWizard from 'merchant/containers/Activation/ActivationWizard';

import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

@connect(state => state.activation, {
  fetchActivationDetails,
  ...ModalActions,
  showNotification,
})
export default class AccountDetailsModal extends Component {
  state = {
    errors: null,
  };

  componentWillMount() {
    if (this.props.accountId) {
      this.props
        .fetchActivationDetails(this.props.accountId)
        .catch(({ errors }) => {
          this.setState({ errors });
        });
    }
  }

  render() {
    let { loading, data } = this.props;

    return (
      <div>
        <ModalHeader
          title="Account Activation Form"
          onCloseClick={this.props.onCloseClick}
        />

        <div class="modal-body">
          {loading ? (
            <div class="page-spinner-container">
              <Spinner />
            </div>
          ) : this.state.errors ? (
            <Alert type="error" message={this.state.errors} />
          ) : (
            <ActivationWizard
              accountId={this.props.accountId}
              data={data}
              callback={() => {
                this.props.showNotification({
                  type: 'success',
                  message: 'The account has been activated',
                });
                this.props.closeModal();

                this.props.fetchAccounts(this.props.skip, this.props.count);
              }}
            />
          )}
        </div>
      </div>
    );
  }
}
