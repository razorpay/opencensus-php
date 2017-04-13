export default ({ label, value, nullText='None' }) => {
  return (
    <div class='list-group-item'>
      {
        typeof label === 'function' ? label() : <span>{label}</span>
      }
      {
        typeof value === 'function' ? value() : <span>{value || nullText}</span>
      }
    </div>
  )
}
