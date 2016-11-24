import { titleCase } from 'rzp/utils/rzp-utils'

const subscriptionsStatusMap = {
  created: 'text-muted',
  active: 'text-success',
  failed: 'text-danger'
}

export default ({ status }) => (
  <span className={subscriptionsStatusMap[status]}>{titleCase(status)}</span>
)
