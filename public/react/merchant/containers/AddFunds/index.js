import React, { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import { Link } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import Alert from 'rzp/ui/Forms/Alert';
import InputField from 'rzp/ui/Forms/InputField';
import { required } from 'rzp/utils/validators';
import * as AddFundsActions from 'merchant/modules/addfunds';
import * as NotificationsActions from 'rzp/modules/notifications';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import { rupeesToPaise } from 'rzp/utils/rzp-utils';

@connect(state => state.session, {
  ...AddFundsActions,
  ...NotificationsActions,
})
@reduxForm({
  form: 'addFunds',
  initialValues: {
    description: 'Add Funds to Account',
    amountInINR: 500,
  },
})
export default class AddFundsContainer extends Component {
  key = null;

  constructor() {
    super(...arguments);
    this.state = {
      isSaving: false,
      status: {},
    };
  }

  componentWillMount() {
    Promise.all([
      this.props.fetchHost().then(response => {
        return this.props.loadCheckout(response.data);
      }),
      this.props.fetchKeys(this.props.user.current).then(key => {
        this.key = key;
      }),
    ]).catch(error => {
      this.setState({
        status: {
          type: 'info',
          message: (
            <span>
              API keys need to be generated before adding funds.{' '}
              <span>
                Keys can be generated{' '}
                <Link to="/keys">
                  <u>here.</u>
                </Link>
              </span>
            </span>
          ),
        },
      });
    });
  }

  addFunds(transaction) {
    this.setState({
      isSaving: true,
    });
    return this.props
      .addFunds(transaction)
      .then(response => {
        this.setState({
          isSaving: false,
        });
        this.props.showNotification({
          type: 'success',
          message: 'Funds added successfully',
        });
      })
      .catch(error => {
        this.setState({
          isSaving: false,
          status: {
            type: 'error',
            message: error,
          },
        });
      });
  }

  openCheckout = fieldProps => {
    let user = this.props.user;
    let amountInPaise = rupeesToPaise(fieldProps.amountInINR);
    let options = {
      key: this.key,
      amount: amountInPaise,
      description: fieldProps.description,
      amountInINR: fieldProps.amountInINR,
      prefill: {
        name: user.name,
        email: user.email,
        contact: user.contact_mobile,
      },
      notes: {
        dashboard: true,
      },
      handler: function(transaction = {}) {
        transaction.amount = amountInPaise;
        this.addFunds(transaction);
      }.bind(this),
    };

    return new Promise((resolve, reject) => {
      try {
        const rzp = new window.Razorpay(options);
        rzp.open();
        resolve();
      } catch (e) {
        reject(`An error occured - ${e.message}`);
      }
    }).catch(error => {
      this.setState({
        status: {
          type: 'error',
          message: error,
        },
      });
    });
  };

  render() {
    let status = this.state.status;
    let { handleSubmit } = this.props;

    return (
      <div>
        <TestModeBanner />

        <div class="content-wrapper content-sm">
          <Alert type={status.type} message={status.message} />

          <p>
            This is just a simple way for you to add money to your account
            balance with Razorpay. This is needed sometimes when you are making
            refunds and your account doesn't have enough funds.
          </p>
          <p>
            Add Funds works over your own account. Therefore, a TDR will be
            deducted on this as well. If you are adding funds for a large
            refund, send us a mail to{' '}
            <a href="mailto:support@razorpay.com" class="highlight">
              support@razorpay.com
            </a>
            .
          </p>

          {this.props.mode === 'test' && (
            <p>Since you are in test mode, this will be a test payment.</p>
          )}

          <form style={{ marginTop: '30px' }}>
            <div class="form-group">
              <label class="control-label label-required">Description</label>
              <Field
                name="description"
                component={InputField}
                class="form-control"
                style={{ maxWidth: '300px' }}
                validate={required()}
              />
            </div>

            <div class="form-group">
              <label class="control-label label-required">Amount</label>
              <Field
                name="amountInINR"
                component={InputField}
                class="form-control"
                style={{ maxWidth: '300px' }}
                validate={required()}
              />
            </div>

            <AsyncButton
              class="btn btn-primary"
              text={this.state.isSaving ? 'Adding Funds...' : 'Add Funds'}
              style={{ marginTop: '10px' }}
              disabled={this.state.isSaving}
              type="button"
              onClick={handleSubmit(this.openCheckout)}
            />
          </form>
        </div>
      </div>
    );
  }
}
