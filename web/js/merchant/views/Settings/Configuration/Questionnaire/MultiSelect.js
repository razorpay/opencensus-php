import React, { useState, isValidElement, useEffect } from 'react';
import { PowerSelectMultiple } from 'react-power-select';
import { useField } from 'formik';
import { classList } from 'common/utils/rzp-utils';

const MultiSelect = ({
  label,
  name,
  error,
  placeholder = '--Select Multiple--',
  required,
  options,
  className = '',
  disabled,
  additionalFieldMaxLength,
  showSpecifyOthersOption = true,
}) => {
  const [ignored, meta, helpers] = useField(name);
  const { value: fieldValue, touched } = meta;
  const [selected, setSelected] = useState(fieldValue || []);
  const additionalData = selected.filter((item) => !options.includes(item))[0];
  const [additionalField, setAdditionalField] = useState(additionalData ? additionalData : '');
  const [takeInput, setTakeInput] = useState(false);

  useEffect(() => {
    helpers.setValue(selected);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selected]);

  useEffect(() => {
    const listSelectedValue = selected.filter((item) => options.includes(item));

    if (additionalField) {
      setSelected([...listSelectedValue, additionalField]);
    } else {
      setSelected(listSelectedValue);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [additionalField]);

  return (
    <div
      className={classList(
        'Input',
        required && 'Input--required',
        error ? 'error' : '',
        className,
        disabled && 'Input--disabled',
      )}
    >
      <div className="Input-label">{label}</div>
      <div className="Input-content">
        <PowerSelectMultiple
          options={options}
          selected={[]}
          disabled={disabled}
          className={`${className} MultiSelect`}
          placeholder={placeholder}
          onChange={(args) => {
            if (isValidElement(args.options[0])) {
              return;
            }

            if (selected.indexOf(args.options[0]) > -1) {
              setSelected(selected.filter((item) => item !== args.options[0]));
            } else {
              setSelected([...selected, ...args.options]);
            }
          }}
          triggerComponent={({ select }) => {
            return (
              <div className="trigger" data-testid="select-items">
                <p style={{ padding: '10px' }}>
                  {selected.length ? `${selected.length} items selected` : placeholder}
                </p>
                <i className={`i ${select.isOpen ? 'i-arrow-up' : 'i-arrow-down'}`} />
              </div>
            );
          }}
          optionComponent={({ option, ...rest }) => {
            if (isValidElement(option)) {
              rest.select.actions.focus();
              return option;
            }
            if (!touched) {
              helpers.setTouched(true);
            }
            const checked = selected.indexOf(option) > -1;

            return (
              <div className="option">
                <label htmlFor={option}>{option}</label>
                <input value={option} id={option} type="checkbox" checked={checked} />
              </div>
            );
          }}
          afterOptionsComponent={({ select }) => {
            const handleInputChange = (e) => {
              setAdditionalField(e.target.value);
            };
            return (
              showSpecifyOthersOption && (
                <div>
                  <div className="more-item">
                    {takeInput ? (
                      <input
                        type="text"
                        placeholder="Enter details here"
                        name={`${name}_extra`}
                        defaultValue={additionalField}
                        onChange={handleInputChange}
                        autoFocus
                        maxLength={additionalFieldMaxLength}
                      />
                    ) : additionalField ? (
                      <div>
                        <span>{additionalField}</span>
                        <span data-testid="editAdditionalField" onClick={() => setTakeInput(true)}>
                          <i className="i i-edit" />
                        </span>
                      </div>
                    ) : (
                      <button className="btn btn-link" onClick={() => setTakeInput(true)}>
                        {'+Others Specify'}
                      </button>
                    )}
                  </div>
                  <div className="after-options">
                    <button
                      className="btn btn-primary m-l"
                      onClick={() => {
                        select.actions.close();
                        setTakeInput(false);
                      }}
                    >
                      Done
                    </button>
                  </div>
                </div>
              )
            );
          }}
        />
        {error ? <div className="Input-error">{error}</div> : ''}
      </div>
    </div>
  );
};

export default MultiSelect;
