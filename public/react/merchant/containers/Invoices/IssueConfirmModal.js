import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import ModalHeader from 'rzp/ui/ModalHeader'
import * as ModalActions from 'merchant/modules/modals'

function validate(values) {
  let errors = {}
  return errors
}

@connect(
  (state) => state.session,
  ModalActions
)
@reduxForm({
  form: 'newInvoice',
  destroyOnUnmount: false,
  validate
})
export default class IssueInvoiceConfirmModal extends Component {
  constructor() {
    super(...arguments)
    this.onIssueClick = ::this.onIssueClick
  }

  onIssueClick(props) {
    return this.props.onIssue(props).then((invoice) => {
      this.props.closeModal()
    })
  }

  render() {
    const { handleSubmit, customer } = this.props
    const isLiveMode = this.props.mode === 'live'
    return (
      <div>
        <ModalHeader
          title='Issue Invoice'
          onCloseClick={this.props.closeModal}
        />

        <form class='form-horizontal'>
          <div class='modal-body'>
            <p>Send Invoice and payment instructions to...</p>
            {
              customer.contact &&
              <div class='rzpChecbox'>
                <Field
                  name='sms_notify'
                  id='sms_notify'
                  component='input'
                  type='checkbox'
                />
                <label for='sms_notify'>{customer.contact}</label>
                {
                  !isLiveMode &&
                  <div class='alert-sm alert-warning' style={{marginLeft: '25px'}}>
                    SMS will not be sent in Test Mode
                  </div>
                }
              </div>
            }

            {
              customer.email &&
              <div class='rzpChecbox'>
                <Field
                  name='email_notify'
                  id='email_notify'
                  component='input'
                  type='checkbox'
                />
                <label for='email_notify'>{customer.email}</label>
              </div>
            }

            <div class='Modal__actions'>
              <AsyncButton
                type='submit'
                class='btn btn-primary btn-block btn-lg'
                text='Issue and Send'
                pendingText='Sending...'
                onClick={handleSubmit(this.onIssueClick)}
              />
            </div>
          </div>
        </form>
      </div>
    )
  }
}
