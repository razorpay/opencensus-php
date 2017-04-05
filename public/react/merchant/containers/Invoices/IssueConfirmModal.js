import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import ModalHeader from 'rzp/ui/ModalHeader'
import Clipboard from 'rzp/ui/Clipboard'
import * as ModalActions from 'merchant/modules/modals'

const selector = formValueSelector('issueInvoice')
@connect(
  (state) => {
    return {
      session: state.session,
      sms_notify:  selector(state, 'sms_notify'),
      email_notify: selector(state, 'email_notify')
    }
  },
  ModalActions
)
@reduxForm({
  form: 'issueInvoice',
})
export default class IssueInvoiceConfirmModal extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      paymentLink: ''
    }
  }

  componentWillMount() {
    let customer = this.props.customer
    if (customer) {
      this.props.initialize({
        sms_notify: !!customer.contact,
        email_notify: !!customer.email,
      })
    }
  }

  onIssueClick = (props) => {
    return this.props.onIssue(props).then((invoice) => {
      if (props.sms_notify || props.email_notify) {
        this.props.closeModal()
      } else {
        this.setState({
          paymentLink: invoice.short_url
        })
      }
    }).catch(() => {
      this.props.closeModal()
    })
  }

  render() {
    const {
      handleSubmit,
      customer,
      sms_notify,
      email_notify,
      disableIssueOnEmptySelection,
    } = this.props

    const isLiveMode = this.props.session.mode === 'live'
    const paymentLink = this.state.paymentLink

    return (
      <div>
        <ModalHeader
          title={ paymentLink ? 'Issued' : 'Issue Invoice' }
          onCloseClick={this.props.closeModal}
        />

        <form class='form-horizontal'>
          <div class='modal-body'>
            {
              paymentLink ?
                <div>
                  <p class='help-block'>
                    Share the following link with the customer manually to receive the payment
                  </p>
                  <Clipboard value={paymentLink} />

                  <div class='Modal__actions'>
                    <AsyncButton
                      type='submit'
                      class='btn btn-primary btn-block btn-lg'
                      text='Done'
                      onClick={this.props.closeModal}
                    />
                  </div>
                </div> :
                <div>
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
                      {/*
                        !isLiveMode &&
                        <div class='alert-sm alert-warning' style={{marginLeft: '25px'}}>
                          SMS will not be sent in Test Mode
                        </div>
                      */}
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

                  <div>A <b>payment link</b> will also be created.</div>
                  <div>The invoice can <b>not</b> be edited after issuing.</div>

                  <div class='Modal__actions'>
                    <AsyncButton
                      type='submit'
                      class='btn btn-primary btn-block btn-lg'
                      text='Issue Invoice'
                      pendingText='Issuing...'
                      disabled={disableIssueOnEmptySelection && !(sms_notify || email_notify)}
                      onClick={handleSubmit(this.onIssueClick)}
                    />
                  </div>
                </div>
            }
          </div>
        </form>
      </div>
    )
  }
}

IssueInvoiceConfirmModal.defaultProps = {
  disableIssueOnEmptySelection: true
}
