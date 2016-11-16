import { PowerSelect } from 'react-power-select'
import { TypeAhead } from 'react-power-select'

export default ({ input, meta, selected, onChange, ...otherProps }) => {
  debugger
  return (
    <TypeAhead
      {...otherProps}
      selected={input.value || selected}
      onChange={(option, select) => {
        debugger
        option = option || {
          id: null,
          name: select.searchTerm
        }
        input.onChange(option)
        onChange(option)
      }}
    />
  )
}
