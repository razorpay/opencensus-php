import { titleCase } from 'rzp/utils/rzp-utils'

const invoicesStatusMap = {
  draft: 'text-muted',
  issued: 'text-info',
  paid: 'text-success',
  expired: 'text-danger'
}

export default ({ status }) => (
  <span className={invoicesStatusMap[status]}>{titleCase(status)}</span>
)
