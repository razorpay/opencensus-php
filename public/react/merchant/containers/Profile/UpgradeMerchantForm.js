import React, { PropTypes, PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { reduxForm, Field } from 'redux-form';
import { required } from 'rzp/utils/validators';
import { showNotification } from 'rzp/modules/notifications';
import { upgradeAccount } from 'merchant/modules/profile';

@connect(null, { showNotification, upgradeAccount })
@reduxForm({
  form: 'merchantUpgradeForm',
})
export default class UpgradeMerchantForm extends PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  confirmUpgrade = props => {
    this.context
      .confirm({
        header: 'Alert',
        message: () => (
          <div class="text-semi-muted">
            <p>
              This will create a merchant account in your business name. Are you sure?
            </p>
          </div>
        ),
        affirmativeLabel: 'Upgrade',
        affirmativePendingLabel: 'Upgrading...',
        abortLabel: 'Cancel',
        action: () => {
          debugger;
          return this.props
            .upgradeAccount(props)
            .then(() => {
              this.props.showNotification({
                type: 'success',
                message: 'Merchant Account Created.',
              });
              setTimeout(() => {
                location.reload;
              }, 400); //TODO: Check behavior, why it's needed
            })
            .catch(err => {
              this.props.showNotification({
                type: 'error',
                message: err.errors,
              });
            });
        },
      })
      .catch(() => {}); // dummy catch to handle confirm abort rejection
  };

  render() {
    const { handleSubmit, invalid } = this.props;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          Upgrade Merchant
        </div>
        <div class="panel-body text-center">
          <div>
            You can upgrade your account to a Merchant Account by giving us your business name
          </div>

          <form
            class="form-inline"
            onSubmit={handleSubmit(this.confirmUpgrade)}
            style={{ marginTop: '25px' }}
          >
            <div class="form-group">
              <label><strong>Business Name: </strong></label> {' '}
              <Field
                component="input"
                type="text"
                placeholder="Acme Inc."
                name="business_name"
                class="form-control"
                autoComplete="off"
                validate={[required()]}
              />
            </div>
            {' '}
            <AsyncButton
              type="submit"
              class="btn btn-info"
              text="Upgrade"
              pendingText="Upgrading..."
              disabled={invalid}
              onClick={handleSubmit(this.confirmUpgrade)}
            />
          </form>
        </div>
      </div>
    );
  }
}
