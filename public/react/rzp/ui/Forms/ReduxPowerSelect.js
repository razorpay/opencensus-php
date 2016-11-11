import { PowerSelect } from 'react-power-select'

export default ({ input, meta, selected, ...otherProps }) => {
  debugger
  return (
    <PowerSelect
      {...otherProps}
      selected={input.value || selected}
      onChange={(option) => {
        input.onChange(option)
      }}
    />
  )
}
