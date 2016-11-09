import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'
import Time from 'rzp/ui/Time'

const SubscriptionListItem = ({ subscription }) => {
  return (
    <tr>
      <td>{subscription.customer_name}</td>
      <td>{subscription.status}</td>
      <td>{subscription.plan_name}</td>
      <td>₹ {subscription.amount}</td>
      <td>
        <Time value={subscription.processed_at} />
      </td>
      <td>
        <Time value={subscription.charge_at} />
      </td>
    </tr>
  )
}

export default ({ subscriptions, isLoading }) => {
  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='6' />
  } else if (subscriptions.length) {
    tableRowComponent = subscriptions.map(
      (subscription) => <SubscriptionListItem key={subscription.id} subscription={subscription} />
    )
  } else {
    tableRowComponent = <EmptyTableRow colSpan='6' message='No Subscriptions found!' />
  }

  return (
    <div className='table-responsive'>
      <table className='table'>
        <thead>
          <tr>
            <th>Customer Name</th>
            <th>Status</th>
            <th>Plan Name</th>
            <th>Amount</th>
            <th>Last billing date</th>
            <th>Next billing date</th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}
