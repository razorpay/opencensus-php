import { PropTypes } from 'react'
import { PowerSelect } from 'react-power-select'
import HighlightedOption from './HighlightedOption'
import QuickAddComponent from './QuickAdd'
import { findBy } from 'rzp/utils/rzp-utils'

export default function ReduxPowerSelect(props) {
  let {
    input,
    meta,
    selected,
    optionLabelPath,
    optionValuePath,
    onQuickAdd,
    onChange,
    ...otherProps
  } = props

  let selectedValue = input.value || selected
  let selectedOption = findBy(props.options, optionValuePath, selectedValue)
  let searchIndices = props.searchIndices || [optionLabelPath]
  let showQuickAdd = !!onQuickAdd

  return (
    <PowerSelect
      {...otherProps}
      selected={selectedOption}
      searchIndices={searchIndices}
      selectedLabel={optionLabelPath}
      optionComponent={({ option, select }) =>
        <HighlightedOption
          option={option}
          select={select}
          optionLabelPath={optionLabelPath}
        />
      }
      afterOptionsComponent={(props) =>
        showQuickAdd && <QuickAddComponent {...props} onClick={onQuickAdd} />
      }
      onChange={(option) => {
        input.onChange(option[optionValuePath])
        onChange(option)
      }}
    />
  )
}

ReduxPowerSelect.defaultProps = {
  optionLabelPath: 'name',
  optionValuePath: 'id',
  showQuickAdd: true,
  onChange: () => {}
}

ReduxPowerSelect.propTypes = {
  optionLabelPath: PropTypes.string,
  optionValuePath: PropTypes.string,
  onQuickAdd: PropTypes.func,
  onChange: PropTypes.func
}
