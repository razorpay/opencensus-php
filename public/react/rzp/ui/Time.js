import Time from 'react-time'

export default ({ value, format='MMM DD, YYYY hh:mm:ss A'}) => {
  return (
    <Time value={value} format={format} />
  )
}
