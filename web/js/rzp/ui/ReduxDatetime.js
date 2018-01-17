import Datetime from 'react-datetime';

export default props => {
  const { placeholder, meta, input, ...otherProps } = props;

  return (
    <div class="custom-date">
      <i class="icon icon-date-range custom-icon" />
      <Datetime
        defaultValue={input.value}
        value={input.value}
        onChange={value => input.onChange(value)}
        inputProps={{
          placeholder: placeholder,
        }}
        {...otherProps}
      />
    </div>
  );
};
