import { PowerSelect } from 'react-power-select'
import { TypeAhead } from 'react-power-select'

export default ({ input, meta, selected, onChange, ...otherProps }) => {
  return (
    <TypeAhead
      {...otherProps}
      selected={input.value || selected}
      onChange={(option, select) => {
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
