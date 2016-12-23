export default (props) => {
  let {
    input,
    validate,
    tagName = 'input',
    meta: { touched, error },
    ...otherProps
  } = props

  return (
    <div>
      {
        tagName === 'textarea' ?
        <textarea {...input} {...otherProps} /> :
        <input {...input} {...otherProps} />
      }

      {touched && error && <div class='text-danger'><small>{error}</small></div>}
    </div>
  )
}
