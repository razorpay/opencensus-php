import Time from 'rzp/ui/Time'
import { OrderStatusLabel } from 'merchant/components/StatusLabel'
import TableBody from '../TableBody'

const OrdersListItem = ({ order }) => {
  return (
    <tr>
      <td>
        <a href={`#/app/orders/${order.id}/details`}>{order.id}</a>
      </td>
      <td>{order.attempts}</td>
      <td>{order.currency}</td>
      <td>{order.amountInINR}</td>
      <td>
        <OrderStatusLabel status={order.status} />
      </td>
      <td>{order.receipt}</td>
      <td>
        <Time value={order.created_at} />
      </td>
    </tr>
  )
}

export default ({ orders, isLoading }) => {
  return (
    <div class='table-responsive'>
      <table class='table table-hover'>
        <thead>
          <tr>
            <th>Order Id</th>
            <th>Attempts</th>
            <th>Currency</th>
            <th>Amount (INR)</th>
            <th>Status</th>
            <th>Receipt</th>
            <th>Created At</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={7}
          rows={orders}
          emptyTableMsg='No Orders found!'
        >
        {
          orders.map((order) =>
            <OrdersListItem
              key={order.id}
              order={order}
            />
          )
        }
        </TableBody>
      </table>
    </div>
  )
}
