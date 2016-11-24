import { titleCase } from 'rzp/utils/rzp-utils'

const invoicesStatusMap = {
  draft: 'label-muted',
  issued: 'label-info',
  paid: 'label-success',
  expired: 'label-danger'
}

export default ({ status }) => (
  <span class={`label ${invoicesStatusMap[status]}`}>{titleCase(status)}</span>
)
