import { titleCase } from 'rzp/utils/rzp-utils'

const subscriptionsStatusMap = {
  active: 'text-success',
  failed: 'text-danger',
  created: 'text-info'
}

export default ({ status }) => (
  <span className={subscriptionsStatusMap[status]}>{titleCase(status)}</span>
)
