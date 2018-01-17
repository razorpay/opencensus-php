import './InputField.styl';

export default props => {
  let {
    input,
    validate,
    tagName = 'input',
    meta: { submitFailed, error },
    showInlineErrorText = true,
    prefix,
    suffix,
    ...otherProps
  } = props;

  let InputComponent = tagName;

  return (
    <div
      class={`InputField ${submitFailed && error ? 'InputField--error' : ''}`}
    >
      <div class="input-group">
        {prefix && <span class="input-group-addon">{prefix}</span>}
        <InputComponent {...input} {...otherProps} />
        {suffix && <span class="input-group-addon">{suffix}</span>}
      </div>

      {showInlineErrorText &&
        submitFailed &&
        error && <div class="InputField__ErrorText text-danger">{error}</div>}
    </div>
  );
};
