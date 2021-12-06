export default (props) => {
  const {
    input,
    validate,
    tagName = 'input',
    meta: { touched, error } = {},
    showInlineErrorText = true,
    ...otherProps
  } = props;

  const InputComponent = tagName;

  return (
    <div class={`InputField ${touched && error ? 'InputField--error' : ''}`}>
      <InputComponent {...input} {...otherProps} />
      {showInlineErrorText && touched && error && (
        <div class="InputField__ErrorText text-danger">{error}</div>
      )}
    </div>
  );
};
