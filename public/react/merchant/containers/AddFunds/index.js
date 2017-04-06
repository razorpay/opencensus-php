import React, { Component } from 'react'
import { connect } from 'react-redux'
import { reduxForm, Field } from 'redux-form'
import AsyncButton from 'react-async-button'
import Alert from 'rzp/ui/Forms/Alert'
import Header from 'rzp/ui/Header'
import InputField from 'rzp/ui/Forms/InputField'
import { required } from 'rzp/utils/validators'
import * as AddFundsActions from 'merchant/modules/addfunds'
import * as NotificationsActions from 'merchant/modules/notifications'

@connect(
  (state) => state.session,
  {
    ...AddFundsActions,
    ...NotificationsActions
  }
)
@reduxForm({
  form: 'addFunds',
  initialValues: {
    description: 'Add Funds to Account',
    amountInINR: 500,
  }
})
export default class AddFundsContainer extends Component {
  key = null

  constructor() {
    super(...arguments)
    this.state = {
      isSaving: false,
      status: {}
    }
  }

  componentWillMount() {
    Promise.all([
      this.props.fetchHost().then((response) => {
        return this.props.loadCheckout(response.data)
      }),
      this.props.fetchKeys(this.props.user.current).then((key) => {
        this.key = key
      })
    ]).catch((error) => {
      this.setState({
        status: {
          type: 'error',
          message: error
        }
      })
    })
  }

  addFunds(transaction) {
    this.setState({
      isSaving: true
    })
    return this.props.addFunds(transaction).then((response) => {
      this.setState({
        isSaving: false
      })
      this.props.showNotification({
        type: 'success',
        message: 'Funds added successfully'
      })
    }).catch((error) => {
      this.setState({
        isSaving: false,
        status: {
          type: 'error',
          message: error
        }
      })
    })
  }

  openCheckout = (fieldProps) => {
    let user = this.props.user
    let amountInPaise = Number(fieldProps.amountInINR) * 100
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
        dashboard: true
      },
      handler: function(transaction = {}) {
        transaction.amount = amountInPaise
        this.addFunds(transaction)
      }.bind(this),
    }

    return new Promise((resolve, reject) => {
      try {
        const rzp = new window.Razorpay(options)
        rzp.open()
        resolve()
      } catch (e) {
        reject(`An error occured - ${e.message}`)
      }
    }).catch((error) => {
      this.setState({
        status: {
          type: 'error',
          message: error
        }
      })
    })
  }

  render() {
    let status = this.state.status
    let { handleSubmit } = this.props

    return (
      <div class='react-root'>
        <Header title='Add Funds' />

        <div class='content-wrapper'>
          <div class='row'>
            <div class='col-sm-6 col-sm-offset-3'>
              <div class='panel panel-default'>
                <div class='panel-heading'>
                  Add Funds - {this.props.modeFormatted} Mode
                </div>

                <div class='panel-body'>
                  <Alert
                    type={status.type}
                    message={status.message}
                  />

                  <p>
                    This is just a simple way for you to add money to your account balance with Razorpay. This is needed sometimes when you are making refunds and your account doesn't have enough funds.
                  </p>
                  <p>
                    Add Funds works over your own account. Therefore, a TDR will be deducted on this
                  as well. If you are adding funds for a large refund, send us a mail to <a href='mailto:support@razorpay.com' class='highlight'>support@razorpay.com</a>.
                  </p>

                  {
                    this.props.mode === 'test' &&
                      <p>
                        Since you are in test mode, this will be a test payment.
                      </p>
                  }

                  <form>
                    <div class='form-group'>
                      <label class='control-label label-required'>Description</label>
                      <Field
                        name='description'
                        component={InputField}
                        class='form-control'
                        validate={required()}
                      />
                    </div>

                    <div class='form-group'>
                      <label class='control-label label-required'>Amount</label>
                      <Field
                        name='amountInINR'
                        component={InputField}
                        class='form-control'
                        validate={required()}
                      />
                    </div>

                    <AsyncButton
                      class='btn btn-primary btn-rounded'
                      text={ this.state.isSaving ? 'Adding Funds...' : 'Add Funds' }
                      disabled={this.state.isSaving}
                      type='button'
                      onClick={handleSubmit(this.openCheckout)}
                    />
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
