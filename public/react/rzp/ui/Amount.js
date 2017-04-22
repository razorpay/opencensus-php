export default ({ value, currency, ...attrs }) => {
  return (
    <span {...attrs}>₹ {(value/100).toFixed(2).replace(/\.00$/, '')}</span>
  )
}
