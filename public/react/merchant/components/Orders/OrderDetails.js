import AsyncButton from 'react-async-button'
import Amount from 'rzp/ui/Amount'
import Time from 'rzp/ui/Time'
import Spinner from 'rzp/ui/Spinner'
import Alert from 'rzp/ui/Forms/Alert'
import ListGroupToggler from 'rzp/ui/ListGroupToggler'
import { OrderStatusLabel, PaymentStatusLabel } from 'merchant/components/StatusLabel'
import TableBody from 'merchant/components/TableBody'

const PaymentList = ({ payment }) => {
  return (
    <tr>
      <td>
        <a
          target='_blank'
          href={`#/app/payments/${payment.id}`}
        >
          {payment.id}
        </a>
      </td>
      <td>
        <PaymentStatusLabel status={payment.status} />
      </td>
      <td>
        <Time
          value={payment.created_at}
          format='DD MMM YYYY, hh:mm:ss a'
        />
      </td>
    </tr>
  )
}

export default (props) => {
  let {
    order,
    payments,
    isLoading,
    statusMsg
  } = props

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
              Order ID: <b>{order.id}</b>
            </div>

            <div class='panel-body'>
              <div class='list-group'>
                <div class='list-group-item'>
                  <span>Amount</span>
                  <Amount value={order.amount} />
                </div>

                <div class='list-group-item'>
                  <span>Currency</span>
                  <span>{order.currency}</span>
                </div>

                <div class='list-group-item'>
                  <span>Attempts</span>
                  <span>{order.attempts}</span>
                </div>

                <div class='list-group-item'>
                  <span>Status</span>
                  <OrderStatusLabel status={order.status} />
                </div>

                {
                  order.attempts > 0 ?
                  <ListGroupToggler
                    label='Payments'
                    onToggleClick={() => props.onTogglePayments(order)}
                  >
                    <table class='table table-hover'>
                      <TableBody
                        colSpan={2}
                        isLoading={payments.loading}
                        rows={payments.items}
                      >
                        {
                          payments.items.map((payment) => <PaymentList key={payment.id} />)
                        }
                      </TableBody>
                    </table>
                  </ListGroupToggler> :
                  <div class='list-group-item'>
                    <span>Payments</span>
                    <span>No Payments </span>
                  </div>
                }

                <div class='list-group-item'>
                  <span>Created At</span>
                  <Time
                    value={order.created_at}
                    format='DD MMM YYYY, hh:mm:ss a'
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
