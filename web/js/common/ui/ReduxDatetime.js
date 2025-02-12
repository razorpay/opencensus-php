import Datetime from 'react-datetime';

export default props => {
  const { placeholder, meta, input, ...otherProps } = props;
  const hasError = meta.touched && meta.error;

  return (
    <div className={`custom-date ${hasError ? 'custom-date-error' : ''}`}>
      <i className="i i-date-range custom-icon" />
      <Datetime
        defaultValue={input.value}
        value={input.value}
        onFocus={props.handleFocus}
        onChange={value => input.onChange(value)}
        inputProps={{
          placeholder: placeholder,
        }}
        {...otherProps}
      />
      {hasError && <span className="error">{meta.error}</span>}
    </div>
  );
};
