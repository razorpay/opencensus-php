import './RadioButton.styl';

export default props => {
  let { label, input, meta, htmlValue, ...otherProps } = props;
  var inputValue = input.value;
  input.value = htmlValue;
  return (
    <div class="RadioButton">
      <label>
        <input
          type="radio"
          {...input}
          {...otherProps}
          checked={htmlValue === inputValue}
        />
        <div>
          <div class="RadioButton__button" />
          <div class="RadioButton__label">
            {typeof label === 'function' ? label() : label}
          </div>
        </div>
      </label>
    </div>
  );
};
