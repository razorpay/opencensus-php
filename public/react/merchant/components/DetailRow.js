export default ({ label, value }) => {
  return (
    <div class='list-group-item'>
      {
        typeof label === 'function' ? label() : <span>{label}</span>
      }
      {
        typeof value === 'function' ? value() : <span>{value}</span>
      }
    </div>
  )
}
