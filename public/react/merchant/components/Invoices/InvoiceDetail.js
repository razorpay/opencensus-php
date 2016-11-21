import Amount from 'rzp/ui/Amount'
import Time from 'rzp/ui/Time'
import Spinner from 'rzp/ui/Spinner'
import ListGroupToggler from 'rzp/ui/ListGroupToggler'
import InvoiceStatus from './InvoiceStatus'

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning'
}

export default ({ invoice, isLoading }) => {
  return (
    <div>
      {
        isLoading ?
        <div class='page-spinner-container'>
          <Spinner />
        </div> :
        <div class='panel panel-default panel-detail'>
          <div class='panel-heading'>
            Invoice Id: <b>{invoice.id}</b>
          </div>
          <div class='panel-body'>
            <div class='list-group'>
              <div class='list-group-item'>
                <Amount class='pull-right' value={invoice.amount} />
                Amount
              </div>

              <div class='list-group-item'>
                <span class='pull-right'>{invoice.short_url}</span>
                Payment Link
              </div>

              <div class='list-group-item'>
                <Time class='pull-right' value={invoice.date} />
                Invoice Date
              </div>

              <div class='list-group-item'>
                <span class='pull-right'>
                  <InvoiceStatus status={invoice.status} />
                </span>
                Invoice Status
              </div>

              <div class='list-group-item'>
                <span class='pull-right'>{invoice.currency}</span>
                Currency
              </div>

              <div class='list-group-item'>
                <span class='pull-right'>{invoice.customer_details.customer_email}</span>
                Email
              </div>

              <div class='list-group-item'>
                <span class='pull-right'>{invoice.customer_details.customer_contact}</span>
                Contact
              </div>

              <div class='list-group-item'>
                <span
                  class={`pull-right ${notificationClassMap[invoice.email_status]}`}
                >
                  {invoice.email_status}
                </span>
                Email Status
              </div>

              <div class='list-group-item'>
                <span
                  class={`pull-right ${notificationClassMap[invoice.sms_status]}`}
                >
                  {invoice.sms_status}
                </span>
                SMS Status
              </div>

              <ListGroupToggler label='Items'>
                {
                  invoice.line_items.map((item) => (
                    <div class='list-group-item'>
                      <Amount class='pull-right' value={item.amount} />
                      {item.name}
                    </div>
                  ))
                }
              </ListGroupToggler>

              <div class='list-group-item'>
                <Time class='pull-right'
                  value={invoice.created_at}
                  format='DD MMM YYYY, hh:mm:ss a'
                />
                Created At
              </div>
            </div>
          </div>
        </div>
      }
    </div>
  )
}
