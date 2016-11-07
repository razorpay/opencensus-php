import Select2 from 'react-select2-wrapper';

export default ({ input, ...otherProps }) => (
  <Select2
    value={input.value}
    onChange={(event) => {
      input.onChange(event.target.value)
    }}
    {...otherProps}
  />
)
