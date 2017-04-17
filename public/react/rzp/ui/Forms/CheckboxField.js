/*
  Custom Checkbox for redux-form Field component
  Since our backend expects 0/1, this component addresses the indiscrepancy between the checkbox values (0/1 & false/true)

  Usage:
    <Field
      name='business_international'
      component={CheckboxField}
    />
*/

export default (props) => {
  let {
    input,
    meta,
    onChange,
    ...otherProps
  } = props

  return (
    <input
      type='checkbox'
      checked={ input.value ? true : false }
      onChange={(event) => {
        input.onChange(event.target.checked ? 1 : 0)
        if (onChange) {
          onChange(event)
        }
      }}
      {...otherProps}
    />
  )
}
