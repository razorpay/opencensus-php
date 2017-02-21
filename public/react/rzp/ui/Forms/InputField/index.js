import './InputField.styl'

export default (props) => {
  let {
    input,
    validate,
    tagName = 'input',
    meta: { touched, error },
    ...otherProps
  } = props

  return (
    <div class={`InputField clearfix ${touched && error ? 'InputField--error' : ''}`}>
      {
        tagName === 'textarea' ?
        <textarea {...input} {...otherProps} /> :
        <input {...input} {...otherProps} />
      }

      {touched && error && <div class='InputField__ErrorText text-danger'>{error}</div>}
    </div>
  )
}
