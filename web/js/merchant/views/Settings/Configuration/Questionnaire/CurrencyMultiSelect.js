import React, { useState, useEffect } from 'react';
import { PowerSelectMultiple } from 'react-power-select';
import { useField } from 'formik';
import { classList } from 'common/utils/rzp-utils';

const frequentlyUsedCurrencies = ['INR', 'USD', 'SGD', 'EUR', 'MYR'];

const CurrencyMultiSelect = ({
  label,
  name,
  placeholder,
  required,
  className,
  error,
  disabled,
}) => {
  const [ignored, meta, helpers] = useField(name);
  const { value, touched } = meta;
  const { setValue, setTouched } = helpers;

  const [selected, setSelectedCountries] = useState([]);
  const [currencies, setCurrencies] = useState([]);

  useEffect(() => {
    const currencyOptions = [
      { label: 'Frequently used', options: [] },
      { label: 'Others', options: [] },
    ];

    Object.keys(window.currencyList).forEach((c) => {
      const currencyObj = {
        currency: c,
        name: window.currencyList[c].name,
        symbol: window.currencyList[c].symbol,
      };
      if (frequentlyUsedCurrencies.indexOf(c) > -1) {
        currencyOptions[0].options.push(currencyObj);
      } else {
        currencyOptions[1].options.push(currencyObj);
      }
    });
    setCurrencies(currencyOptions);

    // Set default selected options
    const defaultSelected = [];
    (value || []).forEach((item) => {
      defaultSelected.push({
        currency: item,
        name: window.currencyList[item].name,
        symbol: window.currencyList[item].symbol,
      });
    });
    setSelectedCountries(defaultSelected);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    const selectedValue = selected.reduce((acc, val) => {
      acc.push(val.currency);
      return acc;
    }, []);
    setValue(selectedValue);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selected]);

  return (
    <div
      className={classList(
        'Input',
        required && 'Input--required',
        error && 'error',
        className,
        disabled && 'Input--disabled',
      )}
    >
      <div className="Input-label">{label}</div>
      <div className="Input-content">
        <PowerSelectMultiple
          options={currencies}
          selected={[]}
          disabled={disabled}
          className={className}
          optionLabelPath="name"
          searchIndices={['name', 'currency']}
          onChange={(args) => {
            if (selected.findIndex((item) => item.currency === args.options[0].currency) > -1) {
              setSelectedCountries(
                selected.filter((item) => item.currency !== args.options[0].currency),
              );
            } else {
              setSelectedCountries([...selected, ...args.options]);
            }
          }}
          triggerComponent={({ select }) => (
            <div className="trigger">
              <p style={{ padding: '8px 12px' }}>
                {selected.length
                  ? `${selected.length} ${
                      selected.length === 1 ? 'currency' : 'currencies'
                    } selected`
                  : placeholder}
              </p>
              <i className={`i ${select.isOpen ? 'i-arrow-up' : 'i-arrow-down'}`} />
            </div>
          )}
          optionComponent={({ option }) => {
            const checked = selected.findIndex((item) => item.currency === option.currency) > -1;
            if (!touched) {
              setTouched(true);
            }
            return (
              <div className="option">
                <label htmlFor={option.currency}>
                  <span className="currency">{option.currency}</span>&nbsp;
                  <span className="symbol">({option.symbol})</span>
                </label>

                <div>
                  <span className="name">{option.name}</span>
                  <input
                    value={option.currency}
                    id={option.currency}
                    type="checkbox"
                    defaultChecked={checked}
                  />
                </div>
              </div>
            );
          }}
          beforeOptionsComponent={({ select }) => {
            return (
              <div className="search">
                <i className="i i-search" />
                <input
                  type="text"
                  placeholder="Search for country or currency"
                  onChange={(e) => {
                    select.actions.search(e.target.value);
                  }}
                />
              </div>
            );
          }}
        />
        {error ? <div className="Input-error">{error}</div> : ''}
      </div>
    </div>
  );
};

export default CurrencyMultiSelect;
