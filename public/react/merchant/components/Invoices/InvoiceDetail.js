import AsyncButton from 'react-async-button'
import Amount from 'rzp/ui/Amount'
import Time from 'rzp/ui/Time'
import Spinner from 'rzp/ui/Spinner'
import Alert from 'rzp/ui/Forms/Alert'
import LineItemReadOnlyTable from './LineItemReadOnlyTable'
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel'

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning'
}

export default (props) => {
  let { invoice, isLoading, statusMsg } = props

  let status = invoice.status
  let isDraft = status === 'draft'
  let isIssued = status === 'issued'
  let isPaid = status === 'paid'
  let isExpired = status === 'expired'

  return (
    <div>
      {
        isLoading ?
        <div class='page-spinner-container'>
          <Spinner />
        </div> :
        <div class='panel-detail-container invoice-details'>
          <Alert type={statusMsg.type} message={statusMsg.message} />

          <div class='invoice-header'>
            <div class='btn-toolbar pull-right'>
              {
                (isDraft || isIssued) &&
                  <button
                    class='btn btn-primary btn-sm'
                    onClick={props.onIssue}
                  >
                    Send Link
                  </button>
              }

              {
                isIssued &&
                  <button
                    class='btn btn-default btn-sm'
                    onClick={props.onExpire}
                  >
                    Expire Link
                  </button>
              }
            </div>

            <h3>{invoice.id}</h3>
          </div>

          <div class='panel panel-default'>
            <div class='panel-heading'>
              Invoice Details
            </div>

            <div class='panel-body'>
              <dl class='dl-horizontal'>
                <dt>Invoice ID:</dt>
                <dd>{invoice.id}</dd>

                <dt>Summary:</dt>
                <dd>{invoice.description || '--'}</dd>

                <dt>Invoice Date:</dt>
                <dd>
                  <Time value={invoice.date} />
                </dd>

                <dt>Receipt:</dt>
                <dd>{invoice.receipt || '--'}</dd>

                <dt>Payment Link:</dt>
                <dd>{invoice.short_url}</dd>

                <dt>Type:</dt>
                <dd>{invoice.type}</dd>

                <dt>Status:</dt>
                <dd>
                  <InvoiceStatusLabel status={invoice.status} />
                </dd>

                <dt>Payment Id:</dt>
                <dd>
                  {
                    invoice.payment_id ?
                    <a href={`#/app/payments/${invoice.payment_id}`}>
                      {invoice.payment_id}
                    </a> : '--'
                  }
                </dd>

                <dt>Paid At</dt>
                <dd>
                  <Time
                    value={invoice.paid_at}
                    format='DD MMM YYYY, hh:mm:ss a'
                  />
                </dd>

                <dt>Amount:</dt>
                <dd>
                  <Amount value={invoice.amount} />
                </dd>

                <dt>Terms & Conditions:</dt>
                <dd>{invoice.terms || '--'}</dd>

                <dt>Notes</dt>
                <dd>
                  {
                    Object.keys(invoice.notes).length ?
                      <dl class='dl-horizontal' style={{'marginLeft': 0}}>
                        {
                          Object.keys(invoice.notes).map((key) => (
                            <div>
                              <dt>{key}</dt>
                              <dd>{invoice.notes[key]}</dd>
                            </div>
                          ))
                        }
                      </dl> : <span>No Notes</span>
                  }
                </dd>
              </dl>
            </div>
          </div>

          <div class='panel panel-default'>
            <div class='panel-heading'>
              Customer Details
            </div>

            <div class='panel-body'>
              <dl class='dl-horizontal'>
                <dt>Name:</dt>
                <dd>{invoice.customer_details.customer_name || '--'}</dd>

                <dt>Email:</dt>
                <dd>
                  {invoice.customer_details.customer_email || '--'}
                  <span
                    style={{ marginLeft: '10px' }}
                    class={`${notificationClassMap[invoice.email_status]}`}
                  >
                    {invoice.email_status ? `(${invoice.email_status})` : ''}
                  </span>
                </dd>

                <dt>Phone:</dt>
                <dd>
                  {invoice.customer_details.customer_contact}
                  <span
                    style={{ marginLeft: '10px' }}
                    class={`${notificationClassMap[invoice.sms_status]}`}
                  >
                    {invoice.sms_status ? `(${invoice.sms_status})` : ''}
                  </span>
                </dd>
              </dl>
            </div>
          </div>
          {
            invoice.line_items.length ?
            <div class='panel panel-default'>
              <div class='panel-heading'>
                Item Details
              </div>

              <div class='panel-body'>
                <LineItemReadOnlyTable line_items={invoice.line_items} />
              </div>
            </div> : ''
          }

          <div class='panel panel-default'>
            <div class='panel-heading'>
              Updates
            </div>

            <div class='panel-body'>
              <dl class='dl-horizontal'>
                <dt>Last Updated At:</dt>
                <dd>
                  <Time
                    value={invoice.updated_at}
                    format='DD MMM YYYY, hh:mm:ss a'
                  />
                </dd>

                <dt>Created At:</dt>
                <dd>
                  <Time
                    value={invoice.created_at}
                    format='DD MMM YYYY, hh:mm:ss a'
                  />
                </dd>
              </dl>
            </div>
          </div>
        </div>
      }
    </div>
  )
}
