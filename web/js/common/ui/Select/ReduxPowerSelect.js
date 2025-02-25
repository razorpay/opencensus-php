import HighlightedOption from './HighlightedOption';
import QuickAddComponent from './QuickAdd';
import { findBy } from 'common/utils/rzp-utils';
import React from 'react';

const ReduxPowerSelectHOC = PowerSelectComponent => props => {
  let {
    input,
    meta,
    selected,
    optionLabelPath,
    selectedOptionLabelPath,
    optionValuePath = 'id',
    onQuickAdd,
    onOptionChange = () => {},
    labelWhenSearchTermBlank,
    labelWhenSearchTermValid,
    maxSearchTermLength,
    className,
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
      className={`${className} ${
        meta.submitFailed && meta.error ? 'error' : ''
      }`}
      selected={selectedOption}
      searchIndices={searchIndices}
      optionLabelPath={optionLabelPath}
      selectedOptionLabelPath={selectedOptionLabelPath || optionLabelPath}
      optionComponent={({ option, select }) => (
        <HighlightedOption
          option={option}
          select={select}
          optionLabelPath={optionLabelPath}
        />
      )}
      afterOptionsComponent={select =>
        showQuickAdd && (
          <QuickAddComponent
            {...{
              labelWhenSearchTermBlank,
              labelWhenSearchTermValid,
              maxSearchTermLength,
              ...select,
            }}
            onClick={onQuickAdd}
          />
        )
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
