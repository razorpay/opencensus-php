export default ({ input, meta: { touched, error }, ...otherProps }) => (
  <div>
    <input {...input} {...otherProps} />
    {touched && error && <div class='text-danger'><small>{error}</small></div>}
  </div>
)
