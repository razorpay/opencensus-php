import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import React from 'react';
import { XStyleDropdownProps } from '../TypeDeclare/XCATypeDeclare';

const XStyleDropdown = ({
  label,
  currentValue,
  itemArray,
  changeFunction,
}: XStyleDropdownProps): React.ReactElement => {
  const handleSelectChange = (value) => () => {
    changeFunction(value);
  };

  const getSelectValue = () => {
    return itemArray.find(({ value }) => value === currentValue)?.name || currentValue;
  };

  return (
    <div className="nss-form__input-form__input-group__input">
      <label htmlFor="business_category">{label}</label>
      <Dropdown>
        <DropdownTrigger className="dropdown-toggle">
          {currentValue === '' ? 'Select' : getSelectValue()} <i className="i i-chevron-down" />
        </DropdownTrigger>
        <DropdownContent>
          <ul className="dropdown-menu">
            <li key="business_category-select-none" onClick={handleSelectChange('')}>
              Select
            </li>
            {itemArray.map(({ value, name }, index) => (
              <li
                className={value === currentValue ? 'selected' : ''}
                key={`business_category-${index}-${value}-${name}`}
                onClick={handleSelectChange(value)}
              >
                {name}
              </li>
            ))}
          </ul>
        </DropdownContent>
      </Dropdown>
    </div>
  );
};

export default XStyleDropdown;
