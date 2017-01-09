import { PropTypes } from 'react'
import HighlightedOption from './HighlightedOption'
import QuickAddComponent from './QuickAdd'
import { findBy } from 'rzp/utils/rzp-utils'

const ReduxPowerSelectHOC = (PowerSelectComponent) => (props) => {
  let {
    input,
    meta,
    selected,
    optionLabelPath,
    optionValuePath = 'id',
    onQuickAdd,
    onChange = () => {},
    ...otherProps
  } = props

  let selectedValue = input.value || selected
  let selectedOption = typeof selectedValue === 'string' ? findBy(props.options, optionValuePath, selectedValue) : selected
  let searchIndices = props.searchIndices || [optionLabelPath]
  let showQuickAdd = !!onQuickAdd

  return (
    <PowerSelectComponent
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
        option = option || input.value
        input.onChange(option[optionValuePath])
        onChange(option)
      }}
    />
  )
}

export default ReduxPowerSelectHOC
