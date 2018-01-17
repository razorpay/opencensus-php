import { PropTypes } from 'react';
import HighlightedOption from './HighlightedOption';
import QuickAddComponent from './QuickAdd';
import { findBy } from 'rzp/utils/rzp-utils';

const ReduxPowerSelectHOC = PowerSelectComponent => props => {
  let {
    input,
    meta,
    selected,
    optionLabelPath,
    optionValuePath = 'id',
    onQuickAdd,
    onOptionChange = () => {},
    ...otherProps
  } = props;

  let selectedValue = input.value || selected;
  let selectedOption =
    typeof selectedValue === 'string'
      ? findBy(props.options, optionValuePath, selectedValue)
      : selected;
  let searchIndices = props.searchIndices || [optionLabelPath];
  let showQuickAdd = !!onQuickAdd;

  return (
    <PowerSelectComponent
      {...otherProps}
      className={`${meta.submitFailed && meta.error ? 'error' : ''}`}
      selected={selectedOption}
      searchIndices={searchIndices}
      optionLabelPath={optionLabelPath}
      optionComponent={({ option, select }) => (
        <HighlightedOption
          option={option}
          select={select}
          optionLabelPath={optionLabelPath}
        />
      )}
      afterOptionsComponent={select =>
        showQuickAdd && <QuickAddComponent {...select} onClick={onQuickAdd} />
      }
      onChange={({ option = '' }) => {
        // input.onChange(option[input.name] || '')
        onOptionChange(option);
      }}
      onEnter={select => {
        showQuickAdd &&
          select.isOpen &&
          select.searchTerm &&
          onQuickAdd(select);
      }}
    />
  );
};

export default ReduxPowerSelectHOC;
