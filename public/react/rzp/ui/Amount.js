export default ({ value, ...attrs }) => {
  return (
    <span {...attrs}>{(value/100).toFixed(2)}</span>
  )
}
