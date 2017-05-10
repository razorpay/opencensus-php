import React, { PropTypes, PureComponent } from 'react';
import AsyncButton from 'react-async-button';
import { reduxForm, Field } from 'redux-form';
import { required } from 'rzp/utils/validators';

@reduxForm({
  form: 'merchantUpgradeForm',
})
export default class UpgradeMerchantForm extends PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  confirmUpgrade = formData => {
    const { handleSubmit, upgradeAccount } = this.props;
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
        affirmativeLabel: 'OK',
        abortLabel: 'Cancel',
        action: upgradeAccount.bind(formData),
      })
      .catch(() => {}); // dummy catch to avoid redux-form error
  };

  render() {
    const { handleSubmit, invalid } = this.props;

    return (
      <div class="row wrapper">
        <div class="panel-heading">
          Upgrade Merchant
        </div>
        <div class="panel-body">
          <div>
            You can upgrade your account to a Merchant Account by giving us your business name
          </div>
          <form
            class="form-inline"
            onSubmit={handleSubmit(this.confirmUpgrade)}
            style={{ marginTop: '25px' }}
          >
            <div class="form-group">
              <label
                style={{ fontWeight: 600, marginRight: '10px' }}
                for="business_name"
              >
                Business Name:
              </label>
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
        <hr style={{ margin: '20px 0 10px' }} />
      </div>
    );
  }
}
