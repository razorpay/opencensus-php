export default ({ label, value }) => {
  if (value === null || value === undefined) {
    value = 'None'
  } else if (value === '') {
    value = '--'
  }

  return (
    <div class='list-group-item'>
      {
        typeof label === 'function' ? label() : <span>{label}</span>
      }
      {
        typeof value === 'function' ? value() : <span>{value + ''}</span>
      }
    </div>
  )
}
