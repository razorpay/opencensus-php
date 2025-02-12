import Input from 'common/new-ui/Input';

const InputField = (props) => {
  const { label, id, value, setValue, autoFocus, placeholder, type, validator } = props;
  return (
    <div className="filter-item">
      <label htmlFor={id} className="color-black">
        {label} <sup className="magic-checkout-color-red"> *</sup>
      </label>
      <Input
        validator={validator}
        autoFocus={autoFocus}
        name={id}
        placeholder={placeholder}
        id={id}
        type={type}
        className="form-input"
        value={value}
        onChange={(e) => setValue(e.target.value)}
      />
    </div>
  );
};
export default InputField;
