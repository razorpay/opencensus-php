import React, { PropTypes, PureComponent } from 'react';
import AsyncButton from 'react-async-button';
import { reduxForm, Field } from 'redux-form';
import { required } from 'rzp/utils/validators';

@reduxForm({
  form: 'merchant',
})
export default class MerchantForm extends PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  confirmUpgrade = () => {
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
        action: handleSubmit(upgradeAccount),
        onAbort: handleSubmit(() => new Promise()),
      })
      .catch(); // dummy catch
  };

  render() {
    const { handleSubmit, invalid } = this.props;

    return (
      <div className="text-center m-b">
        <div>
          You can upgrade your account to a Merchant Account by giving us your business name
        </div>
        <form
          className="form-inline"
          onSubmit={handleSubmit(this.confirmUpgrade)}
          style={{ marginTop: 25 + 'px' }}
        >
          <div className="form-group">
            <label
              style={{ fontWeight: 600, marginRight: 10 + 'px' }}
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
            class="btn btn-info btn-rounded"
            text="Upgrade"
            pendingText="Upgrading..."
            disabled={invalid}
            onClick={handleSubmit(this.confirmUpgrade)}
          />
        </form>
        <hr />
      </div>
    );
  }
}
