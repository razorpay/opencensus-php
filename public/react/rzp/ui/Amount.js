export default ({ value }) => {
  return (
    <span>{(value/100).toFixed(2)}</span>
  )
}
