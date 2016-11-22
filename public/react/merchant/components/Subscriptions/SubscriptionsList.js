import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'
import SubscriptionStatus from './SubscriptionStatus'
import Time from 'rzp/ui/Time'
import { findBy } from 'rzp/utils/rzp-utils'

const SubscriptionListItem = ({ subscription, plans }) => {
  let subscribedPlan = findBy(plans, 'id', subscription.plan_id) || {}
  return (
    <tr>
      <td>{subscription.customer.name}</td>
      <td>
        <SubscriptionStatus status={subscription.status} />
      </td>
      <td>{subscribedPlan.name}</td>
      <td>
        <Time value={subscription.processed_at} />
      </td>
      <td>
        <Time value={subscription.charge_at} />
      </td>
      <td class='text-right'>{subscription.amount}</td>
    </tr>
  )
}

export default ({ subscriptions, plans, isLoading }) => {
  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='6' />
  } else if (subscriptions.length) {
    tableRowComponent = subscriptions.map((subscription) =>
      <SubscriptionListItem
        key={subscription.id}
        subscription={subscription}
        plans={plans}
      />
    )
  } else {
    tableRowComponent = <EmptyTableRow colSpan='6' message='No Subscriptions found!' />
  }

  return (
    <div class='table-responsive'>
      <table class='table'>
        <thead>
          <tr>
            <th>Customer Name</th>
            <th>Status</th>
            <th>Plan Name</th>
            <th>Last billing date</th>
            <th>Next billing date</th>
            <th class='text-right'>Amount (INR)</th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}
