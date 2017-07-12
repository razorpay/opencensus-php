import Datetime from 'react-datetime';

export default props => {
  const { placeholder, meta, input, ...otherProps } = props;

  return (
    <Datetime
      defaultValue={input.value}
      value={input.value}
      onChange={value => input.onChange(value)}
      inputProps={{
        placeholder: placeholder,
      }}
      {...otherProps}
    />
  );
};
