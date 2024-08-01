import React, { useCallback, useState } from 'react';
import debounce from 'lodash/debounce';

import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  Operator,
  Option,
  Parameter,
  MappedProiders,
  LogicalOperator,
} from 'merchant/views/Optimizer/types';
import { gatewayLogos, SMART_ROUTER } from 'merchant/views/Optimizer/utils';

import { ClickOutside } from './ClickOutside';

interface SelectProps {
  selected: (Operator | Option | Parameter | MappedProiders | LogicalOperator)[];
  class?: string;
  placeholder: string;
  options: (Operator | Option | Parameter | MappedProiders | LogicalOperator)[];
  multiple?: boolean;
  select: (value: (Operator | Parameter | MappedProiders | LogicalOperator)[]) => void;
  searchable?: boolean;
  selectedOperator?: string;
}

const Select = (props: SelectProps) => {
  const {
    selected: props_selected,
    class: custom_class,
    placeholder,
    options,
    multiple: isMultiple,
    select,
    searchable: isSearchable,
    selectedOperator,
  } = props;
  const [shouldShow, setShouldShow] = useState<boolean>(false);
  const [filteredOptions, setFilteredOptions] = useState<
    (Operator | Option | Parameter | MappedProiders | LogicalOperator)[]
  >([]);

  let selected = {};
  if (props_selected?.length > 0) {
    props_selected.forEach((option, index, obj) => {
      selected[option.id] = option;
      if ((option as Option | MappedProiders)?.disabled) {
        obj.splice(index, 1);
      }
    });
  }
  const selectedValue = props_selected?.map(({ name }) => name) ?? [];
  const IDS = [
    'upi_intent',
    'upi_collect',
    'BARB_R',
    'PUNB_R',
    'LAVB_R',
    'ANDB_C',
    'DLXB_C',
    'IBKL_C',
    'LAVB_C',
    'RATN_C',
    'SVCB_C',
    'YESB_C',
  ];

  const onToggle = useCallback(() => {
    setShouldShow((prevState) => !prevState);
    setFilteredOptions(options);
  }, [options]);

  // To filter options based on search
  const filterOnSearch = useCallback(
    ({ target }) => {
      const { value } = target;
      if (value !== '') {
        const pattern = new RegExp(`${value}`, 'i');
        setFilteredOptions(options?.filter(({ name }) => pattern.test(name)) ?? []);
      } else {
        setFilteredOptions(options);
      }
    },
    [options],
  );

  const selectOption = (e, option) => {
    if (selectedOperator === 'in') {
      e.stopPropagation();
    }
    if (!option.disabled) {
      if (!isMultiple) {
        selected = { [option.id]: option };
      } else if (selected[option.id]) {
        delete selected[option.id];
      } else {
        selected[option.id] = option;
      }
      const value = Object.keys(selected).map((key) => selected[key]);
      select(value);
    }
  };

  return (
    <div className="input-select-container" onClick={onToggle}>
      <input
        readOnly
        type="text"
        className={`Input--vTop form-control${custom_class ? ` ${custom_class}` : ''}`}
        name="select-input"
        value={selectedValue}
        placeholder={placeholder}
      />
      <i className="select-chev i i-chevron-down" />
      {shouldShow && (
        <ClickOutside onClickOutside={onToggle}>
          <div className="input-select">
            {isSearchable ? (
              <div className="select-search-wrapper">
                <Input placeholder="Search" onChange={debounce(filterOnSearch, 300)} autoFocus />
              </div>
            ) : null}
            <ul className="unlisted">
              {filteredOptions?.map((option, index) => {
                let gateway;
                if (typeof option?.id === 'string' && IDS.indexOf(option?.id) === -1) {
                  const id = option?.id?.split('_');
                  if (option?.id === 'razorpay') {
                    gateway = id[0];
                  } else {
                    id.pop();
                    gateway = id.join('_');
                  }
                }
                return (
                  <span key={index}>
                    <li
                      className={(option as MappedProiders).disabled ? 'disabled' : ''}
                      key={index}
                      onClick={(e) => selectOption(e, option)}
                    >
                      <div>
                        <div className="row">
                          <div className="col-xs-10 p-0">
                            {option?.id != SMART_ROUTER && gateway && (
                              <div className="recommended-provider-img-block">
                                <img src={gatewayLogos[gateway]} alt={gateway} />
                              </div>
                            )}
                            <b className="optn-text">{option.name}</b>
                            {option.id === SMART_ROUTER ? (
                              <span className="recommended-provider">
                                <span className="recommended-provider-text">RECOMMENDED</span>
                                {!(option as MappedProiders).disabled ? (
                                  <Popover theme="dark" align="right">
                                    <PopoverBody>
                                      <div>Recommended for better success rate</div>
                                    </PopoverBody>
                                  </Popover>
                                ) : null}
                              </span>
                            ) : null}
                          </div>
                          <div className="col-xs-2">
                            {selected[option.id] ? <i className="i i-tick select-tick" /> : null}
                          </div>
                        </div>
                      </div>
                      {(option as Parameter).description ? (
                        <div className="sub-p">{(option as Parameter).description}</div>
                      ) : null}
                    </li>
                    {(option as MappedProiders).disabled ? (
                      <Popover theme="dark" align="right">
                        <PopoverBody>
                          <div>{(option as MappedProiders).disabled_message}</div>
                        </PopoverBody>
                      </Popover>
                    ) : null}
                  </span>
                );
              })}
            </ul>
          </div>
        </ClickOutside>
      )}
    </div>
  );
};

export default Select;
