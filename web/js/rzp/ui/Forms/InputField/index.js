export default props => {
  let {
    input,
    validate,
    tagName = 'input',
    meta: { submitFailed, error } = {},
    showInlineErrorText = true,
    ...otherProps
  } = props;

  let InputComponent = tagName;

  return (
    <div
      class={`InputField ${submitFailed && error ? 'InputField--error' : ''}`}
    >
      <InputComponent {...input} {...otherProps} />
      {showInlineErrorText &&
        submitFailed &&
        error && <div class="InputField__ErrorText text-danger">{error}</div>}
    </div>
  );
};
