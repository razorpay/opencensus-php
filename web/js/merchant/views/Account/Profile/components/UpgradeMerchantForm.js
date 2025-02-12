import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import { showNotification } from 'merchant_common/reducers/notifications';
import { upgradeAccount } from 'merchant/reducers/profile';
import { compose } from 'redux';

class UpgradeMerchantForm extends PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  confirmUpgrade = (props) => {
    this.context
      .confirm({
        header: 'Alert',
        message: () => (
          <div className="text-semi-muted">
            <p>This will create a merchant account in your business name. Are you sure?</p>
          </div>
        ),
        affirmativeLabel: 'Upgrade',
        affirmativePendingLabel: 'Upgrading...',
        abortLabel: 'Cancel',
        action: () => {
          return this.props
            .upgradeAccount(props)
            .then(() => {
              this.props.showNotification({
                type: 'success',
                message: 'Merchant Account Created.',
              });
              setTimeout(() => {
                location.reload();
              }, 400);
            })
            .catch((err) => {
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
      <div className="panel panel-default">
        <div className="panel-heading">Upgrade Merchant</div>
        <div className="panel-body text-center">
          <div>
            You can upgrade your account to a Merchant Account by giving us your business name
          </div>

          <form
            className="form-inline"
            onSubmit={handleSubmit(this.confirmUpgrade)}
            style={{ marginTop: '25px' }}
          >
            <div className="form-group">
              <label>
                <strong>Business Name: </strong>
              </label>{' '}
              <Field
                component="input"
                type="text"
                placeholder="Acme Inc."
                name="business_name"
                className="form-control"
                autoComplete="off"
                validate={[required()]}
              />
            </div>{' '}
            <AsyncButton
              type="submit"
              className="btn btn-info"
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

export default compose(
  connect(null, { showNotification, upgradeAccount }),
  reduxForm({
    form: 'merchantUpgradeForm',
  }),
)(UpgradeMerchantForm);
