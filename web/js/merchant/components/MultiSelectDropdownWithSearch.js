import React, { useState, useCallback } from 'react';
import Input from 'common/new-ui/Input';
import { titleCase } from 'common/utils/rzp-utils';
import debounce from 'lodash/debounce';

/**
 * Dropdown for multi select options with search functionality
 * @param {object} options dropdown options - array of strings
 * @param {object} selected dropdown selected options - array of strings
 * @param {boolean} disabled component disabled or not
 * @param {string} placeholder placeholder value
 * @param {string} searchBarPlaceholder search bar placeholder value
 * @param {function} handleCheckBoxChange function to select/deselect checkbox
 * @returns {JSX.Element} JSX element
 */
export const MultiSelectDropdownWithSearch = ({
  options,
  selected,
  disabled,
  placeholder,
  searchBarPlaceholder,
  handleCheckBoxChange,
}) => {
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [filteredOptions, setFilteredOptions] = useState(options);

  // To filter options based on search
  const filterOnSearch = useCallback(
    (event) => {
      const { value } = event.target;
      if (value) {
        const pattern = new RegExp(`${value}`, 'i');
        setFilteredOptions(options.filter((item) => pattern.test(item)));
      } else {
        setFilteredOptions(options);
      }
    },
    [options],
  );

  // To collapse the dropdown options
  const collapseInput = useCallback(() => {
    setIsCollapsed(!isCollapsed);
    setFilteredOptions(options);
  }, [isCollapsed, options]);

  // To remove option using option pill/tag
  const removeOption = useCallback(
    (e, item) => {
      // Mimic checkbox event data for deselecting via option pills/tags
      const event = {
        target: { checked: false },
      };
      handleCheckBoxChange(event, item);
      e.stopPropagation();
    },
    [handleCheckBoxChange],
  );

  // To render checkbox option
  const renderOption = (item) => {
    const checked = selected.indexOf(item) !== -1;
    return (
      <div className="col-xs-6" key={`${item}-${Number(checked)}`}>
        <Input.Check
          fieldLabel={titleCase(item)}
          checkboxMaskLabel={false}
          checked={checked}
          onChange={(e) => handleCheckBoxChange(e, item)}
          disabled={disabled}
        />
      </div>
    );
  };

  // To render selected pill/tag option
  const renderSelectedOption = (item) => {
    return (
      <span
        key={item}
        className={`status-label label option-pill${disabled ? ' option-pill-disabled' : ''}`}
        onClick={!disabled ? (e) => removeOption(e, item) : null}
      >
        {titleCase(item)}
        <i className="i i-close" />
      </span>
    );
  };

  return (
    <div className="multi-select-dropdown-with-search-block">
      <div
        className={`selected-options-block${isCollapsed ? ' remove-border-bottom' : ''}`}
        onClick={!disabled ? collapseInput : null}
      >
        {selected.length <= 0 ? (
          <span className="placeholder">{placeholder}</span>
        ) : (
          <>
            {selected.slice(0, 3).map((item) => renderSelectedOption(item))}
            {selected.length > 3 && (
              <>
                ...
                <span className="status-label label count-pill">+{selected.length - 3}</span>
              </>
            )}
          </>
        )}
        <i className={`i${isCollapsed ? ' i-chevron-up' : ' i-chevron-down'}`} />
      </div>
      {isCollapsed && (
        <div className="options-block">
          <Input
            addonAfter={<i className="i i-search" />}
            type="text"
            name="search"
            placeholder={searchBarPlaceholder}
            className="Input--vLeft"
            onChange={debounce(filterOnSearch, 300)}
            disabled={disabled}
          />
          <div className="row">
            <div className="col-xs-12">{filteredOptions.map((item) => renderOption(item))}</div>
          </div>
        </div>
      )}
    </div>
  );
};
