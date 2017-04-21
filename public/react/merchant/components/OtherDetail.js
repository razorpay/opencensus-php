import { getType } from 'rzp/utils/entity'
import { humanize } from 'rzp/utils/rzp-utils'
import Amount from 'rzp/ui/Amount'
import Time from 'rzp/ui/Time'
import DetailRow from './DetailRow'

export default ({label, value, entity = {}}) => {
  let type = getType(label, value)
  let currency = entity.currency || 'INR'
  let val = value

  switch (type) {
    case 'timestamp':
      val = () => <Time value={value} format='DD MMM YYYY, hh:mm:ss a'/>
      break;

    case 'amount_inr':
      val = () => <Amount value={value} />
      break;

    case 'amount':
      val = () => <Amount value={value} currency={currency} />
      break;
  }

  label = typeof label === 'function' ? label : humanize(label)

  return (
    <DetailRow
      label={label}
      value={val}
    />
  )
}