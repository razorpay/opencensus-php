import React from 'react';
import { ActionList, ActionListSection, ActionListItem } from '@razorpay/blade/components';
import { SectionOption, Option } from '@libs/web-nexus/common/components/Dropdown/types';
import { ActionListWrapperProps } from './types';

const ActionListWrapper = ({
  isMultipleSelection,
  selectedOptions,
  tempSelectedOptions,
  onOptionClick,
  options,
}: ActionListWrapperProps): JSX.Element => {
  const _selectedOptions = isMultipleSelection ? tempSelectedOptions : selectedOptions;
  return (
    <ActionList>
      {options.map((option) => {
        if ((option as SectionOption).section) {
          const { name: sectionName, options: sectionOptions } = (option as SectionOption).section;
          return (
            <ActionListSection key={sectionName} title={sectionName}>
              {sectionOptions.map((sectionOption) => {
                const { title, value: optionValue, leading } = sectionOption;
                const isSelected = _selectedOptions.some(
                  // handling the case where the option is falsy
                  (option) => optionValue === option?.value,
                );
                return (
                  <ActionListItem
                    key={optionValue}
                    onClick={onOptionClick(sectionOption)}
                    isSelected={isSelected}
                    title={title}
                    value={optionValue}
                    leading={leading}
                  />
                );
              })}
            </ActionListSection>
          );
        } else {
          const { title, value: optionValue, leading } = option as Option;
          const isSelected = _selectedOptions.some(
            // handling the case where the option is falsy
            (option) => optionValue === option?.value,
          );
          return (
            <ActionListItem
              key={optionValue}
              onClick={onOptionClick(option as Option)}
              isSelected={isSelected}
              title={title}
              value={optionValue}
              leading={leading}
            />
          );
        }
      })}
    </ActionList>
  );
};

export default ActionListWrapper;
