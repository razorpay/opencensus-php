export default ({ tagName = 'input', input, meta: { touched, error }, ...otherProps }) => (
  <div>
    {
      tagName === 'textarea' ?
      <textarea {...input} {...otherProps} /> :
      <input {...input} {...otherProps} />
    }

    {touched && error && <div class='text-danger'><small>{error}</small></div>}
  </div>
)
