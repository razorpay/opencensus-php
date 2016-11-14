import { PowerSelect } from 'react-power-select'
import { TypeAhead } from 'react-power-select'

export default ({ input, meta, selected, ...otherProps }) => {
  return (
    <TypeAhead
      {...otherProps}
      selected={input.value || selected}
      onChange={(option) => {
        input.onChange(option)
      }}
    />
  )
}
