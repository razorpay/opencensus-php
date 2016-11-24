import AsyncButton from 'react-async-button'
import Amount from 'rzp/ui/Amount'
import Time from 'rzp/ui/Time'
import Spinner from 'rzp/ui/Spinner'
import Alert from 'rzp/ui/Forms/Alert'
import ListGroupToggler from 'rzp/ui/ListGroupToggler'
import InvoiceStatus from './InvoiceStatus'

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning'
}

export default (props) => {
  let { invoice, isLoading, statusMsg } = props
  return (
    <div>
      {
        isLoading ?
        <div class='page-spinner-container'>
          <Spinner />
        </div> :
        <div class='panel-detail-container'>
          <Alert type={statusMsg.type} message={statusMsg.message} />
          <div class='panel panel-default'>
            <div class='panel-heading'>
              Invoice Id: <b>{invoice.id}</b>
            </div>

            <div class='panel-body'>
              <div class='list-group'>
                <div class='list-group-item'>
                  <span class='pull-right'>
                    {
                      invoice.customer_details.customer_email ?
                      invoice.customer_details.customer_email : '--'
                    }
                  </span>
                  Customer Email
                </div>

                <div class='list-group-item'>
                  <span class='pull-right'>
                    {
                      invoice.customer_details.customer_contact ?
                      invoice.customer_details.customer_contact : '--'
                    }
                  </span>
                  Customer Contact
                </div>

                <div class='list-group-item'>
                  <Amount class='pull-right' value={invoice.amount} />
                  Amount
                </div>

                <div class='list-group-item'>
                  <span class='pull-right'>{invoice.currency}</span>
                  Currency
                </div>

                <div class='list-group-item'>
                  <Time class='pull-right' value={invoice.date} />
                  Invoice Date
                </div>

                <div class='list-group-item'>
                  <span class='pull-right'>{invoice.short_url}</span>
                  Payment Link
                </div>

                <div class='list-group-item'>
                  <span class='pull-right'>
                    <InvoiceStatus status={invoice.status} />
                  </span>
                  Invoice Status
                </div>

                <div class='list-group-item'>
                  <span class='pull-right'>
                    {
                      invoice.payment_id ?
                      <a href={`#/app/payments/${invoice.payment_id}`}>
                        {invoice.payment_id}
                      </a> : '--'
                    }
                  </span>
                  Payment Id
                </div>

                <div class='list-group-item'>
                  <span class='pull-right'>
                    {
                      invoice.paid_at ?
                      <Time class='pull-right'
                        value={invoice.paid_at}
                        format='DD MMM YYYY, hh:mm:ss a'
                      /> : '--'
                    }
                  </span>
                  Paid At
                </div>

                <div class='list-group-item'>
                  <span
                    class={`pull-right ${notificationClassMap[invoice.email_status]}`}
                  >
                    {invoice.email_status ? invoice.email_status : '--'}
                  </span>
                  Email Status
                </div>

                <div class='list-group-item'>
                  <span
                    class={`pull-right ${notificationClassMap[invoice.sms_status]}`}
                  >
                    {invoice.sms_status ? invoice.sms_status : '--'}
                  </span>
                  SMS Status
                </div>

                <ListGroupToggler label='Items'>
                  {
                    invoice.line_items.map((item, index) => (
                      <div class='list-group-item' key={index}>
                        <Amount class='pull-right' value={item.amount} />
                        {item.name}
                      </div>
                    ))
                  }
                </ListGroupToggler>

                <div class='list-group-item'>
                  <span class='pull-right'>{invoice.type}</span>
                  Type
                </div>

                <div class='list-group-item'>
                  <Time class='pull-right'
                    value={invoice.created_at}
                    format='DD MMM YYYY, hh:mm:ss a'
                  />
                  Created At
                </div>
              </div>

              <div class='text-center'>
                <div class='btn-toolbar inline'>
                  <AsyncButton
                    class='btn btn-primary btn-rounded'
                    text={
                      !invoice.sms_status ? 'Send SMS' : 'Resend SMS'
                    }
                    pendingText='Sending SMS...'
                    onClick={() => props.onNotify('sms')}
                  />

                  <AsyncButton
                    class='btn btn-primary btn-rounded'
                    text={
                      !invoice.email_status ? 'Send Email' : 'Resend Email'
                    }
                    pendingText='Sending Email...'
                    onClick={() => props.onNotify('email')}
                  />
                </div>
              </div>

            </div>
          </div>
        </div>
      }
    </div>
  )
}
