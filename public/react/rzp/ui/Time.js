import Time from 'react-time'
import moment from 'moment'

export default ({ value, format='DD MMM YYYY', ...attrs}) => {
  return (
    <Time value={moment.unix(value)} format={format} {...attrs} />
  )
}
