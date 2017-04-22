import './InputField.styl';

export default props => {
  let {
    input,
    validate,
    tagName = 'input',
    meta: { submitFailed, error },
    showInlineErrorText = true,
    ...otherProps
  } = props;

  return (
    <div
      class={`InputField ${submitFailed && error ? 'InputField--error' : ''}`}
    >
      {tagName === 'textarea'
        ? <textarea {...input} {...otherProps} />
        : <input {...input} {...otherProps} />}

      {showInlineErrorText &&
        submitFailed &&
        error &&
        <div class="InputField__ErrorText text-danger">{error}</div>}
    </div>
  );
};
