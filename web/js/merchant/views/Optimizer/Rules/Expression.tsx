import React, { useState } from 'react';
import { Amount } from '@razorpay/blade/components';

import { AmountTooltip } from 'common/ui/Amount';
import { Precondition, Operands, Parameter } from 'merchant/views/Optimizer/types';
import { OPERATORS, getValue } from 'merchant/views/Optimizer/utils';

import Select from './Select';

interface ExpressionProps {
  expression: Precondition | Operands;
  update: (expression: Precondition | Operands) => void;
  parameters: Parameter[];
  readonly: boolean;
  onClose: () => void;
}

export const Expression = ({
  expression,
  update,
  parameters,
  readonly,
  onClose,
}: ExpressionProps): JSX.Element => {
  const [shouldShowBinNumberErorr, setshouldShowBinNumberErorr] = useState(false);

  const getParameterValue = (value: string): Parameter | undefined => {
    return parameters.find((parameter) => parameter.value === value);
  };

  const renderAmount = (value: string): JSX.Element | null => {
    if (value) {
      return (
        <>
          {value.split(',').map((item, key, arr) => (
            <>
              <Amount key={`${item}_${key}`} value={Number(item) / 100} />
              {key !== arr.length - 1 && <span>, </span>}
            </>
          ))}
        </>
      );
    }
    return null;
  };

  const handleAmountChange = (e, handleChange, valueType) => {
    const value = handleChange(e);
    update({
      ...expression,
      operands: [
        expression?.operands?.[0],
        {
          ...expression?.operands?.[1],
          value,
          type: valueType,
        },
      ],
    });
  };

  const getAmountComp = (val, handleChange, valueType) => (
    <div className="input-group">
      <AmountTooltip
        currency={window.rzp_user?.merchant?.currency || 'INR'}
        parentQuerySelector=".ReactModal__Overlay .ReactModal__Content"
        customClass="input-group-addon"
      />
      <input
        type="number"
        placeholder="Enter Amount"
        name="amountInINR"
        className="form-control"
        value={val ? val / 100 : val}
        onChange={(e) => handleAmountChange(e, handleChange, valueType)}
        min="0"
      />
    </div>
  );

  const handleAmountBetweenChange = (index) => (e) => {
    const value = expression?.operands?.[1].value.split(',');
    value[index] = e.target.value && e.target.value >= 0 ? String(e.target.value * 100) : '';
    const valueString = value.join(',');
    return valueString;
  };

  const parameter = getParameterValue(expression?.operands?.[0].value);
  const operatorsList = OPERATORS.filter((o) => {
    const p = parameter;
    if (p) {
      const ops = Object.keys(p.operators);
      return ops.indexOf(o.value) !== -1;
    } else {
      return true;
    }
  });
  const values = parameter
    ? parameter.values.map((p) => {
        return {
          id: p.value,
          name: p.value,
          value: p.value,
          disabled_message: p.disabled_message,
          disabled: p.disabled,
        };
      })
    : [];
  const rhsType =
    expression?.operands?.[0].value && expression.value
      ? parameter?.operators?.[expression.value]
      : {};
  let valueType;
  if (parameter) {
    valueType = parameter.type;
    if (expression.value == 'in' || expression.value == 'between') {
      valueType = 'array';
    }
  }

  const handleMultipleChange = (e) => {
    const value = e.target.value;
    update({
      ...expression,
      operands: [
        expression?.operands?.[0],
        {
          ...expression?.operands?.[1],
          value,
          type: valueType,
        },
      ],
    });
  };

  const handleBinNumberChange = (e) => {
    const value = e.target.value;
    const values = value.split(',');
    let shouldUpdate = true;
    values.forEach((item, index) => {
      if (index < values.length - 1) {
        if (!(item.length >= 4 && item.length <= 6)) {
          shouldUpdate = false;
          if (!shouldShowBinNumberErorr) {
            setshouldShowBinNumberErorr(true);
          }
        }
      }
      if (shouldShowBinNumberErorr && item.length >= 4 && item.length <= 6) {
        setshouldShowBinNumberErorr(false);
      }
    });
    const arr = values.map((item, index) => {
      const res = item.trim();
      if (isNaN(Number(res)) || (res === '' && index !== values.length - 1)) {
        shouldUpdate = false;
      }
      return res;
    });
    if (shouldUpdate) {
      const newVal = arr.map((item) => {
        if (item !== '') {
          return item;
        }
        return null;
      });
      update({
        ...expression,
        operands: [
          expression?.operands?.[0],
          {
            ...expression?.operands?.[1],
            value: newVal.join(','),
            type: valueType,
          },
        ],
      });
    }
  };

  const outerDivClick = (e) => {
    e.stopPropagation();
    const parent = e.target as HTMLDivElement;
    if (parent.classList[0] === 'expression-row') {
      if (onClose) {
        onClose();
      }
    }
  };

  return (
    <div
      className={`expression-row ${readonly ? 'expression-row-readonly' : ''}`}
      onClick={outerDivClick}
    >
      {readonly ? (
        <div className="row">
          <div className="col-xs-12">
            <div className="expression-readonly-high">
              When <b>{parameter?.name}</b> {getValue('operator', expression.value).name}{' '}
              {parameter?.value === '$payment.navigator_amount' ? (
                renderAmount(expression?.operands?.[1].value)
              ) : (
                <b>{expression?.operands?.[1].value}</b>
              )}
            </div>
          </div>
        </div>
      ) : (
        <div style={{ display: 'flex' }}>
          <div style={{ width: '12%' }}>
            <span className="mid-text">When</span>
          </div>
          <div style={{ width: '24%' }} className="p0 select-parameter">
            <Select
              placeholder="Select Parameter"
              options={parameters}
              selected={expression?.operands?.[0].value
                .split(',')
                .map((v) => parameters.find((p) => p.value == v))
                .filter((i) => i)}
              select={(values) => {
                const operandValues = values.map((v) => v.value).join(',');
                update({
                  ...expression,
                  operands: [
                    { ...expression?.operands?.[0], value: operandValues },
                    expression?.operands?.[1],
                  ],
                  type: null,
                  value: '',
                });
              }}
            />
          </div>
          <div style={{ width: '3%' }}>
            <span className="mid-text">is</span>
          </div>
          <div style={{ width: '30%' }} className="col-xs-2 select-operator">
            <Select
              placeholder="Select Connection"
              options={operatorsList}
              selected={OPERATORS.filter(({ value }) => value === expression?.value)}
              select={(values) => {
                update({
                  ...expression,
                  value: values[0].value,
                  type: getValue('operator', values[0].value).type,
                  operands: [
                    {
                      ...expression?.operands?.[0],
                      type: 'variable',
                    },
                    {
                      ...expression?.operands?.[1],
                      value: '',
                    },
                  ],
                });
              }}
            />
          </div>
          <div style={{ width: '40%' }} className="col-xs-4 select-value">
            {(() => {
              let jsx = (
                <Select
                  multiple={rhsType?.multiple}
                  placeholder="Select Comparing Value"
                  options={values}
                  searchable
                  selected={expression?.operands?.[1].value
                    .split(',')
                    .map((v) => values.find((p) => p.value == v))
                    .filter((i) => i)}
                  select={(values) => {
                    const operandValues = values.map((v) => v.value).join(',');
                    update({
                      ...expression,
                      operands: [
                        {
                          ...expression?.operands?.[0],
                          type: 'variable',
                        },
                        {
                          ...expression?.operands?.[1],
                          value: operandValues,
                          type: valueType,
                        },
                      ],
                    });
                  }}
                  selectedOperator={expression.value}
                />
              );
              if (
                rhsType?.type == 'input' &&
                !rhsType?.multiple &&
                parameter?.value !== '$payment.navigator_amount'
              ) {
                jsx = (
                  <div className="row">
                    <div className="col-xs-12">
                      <input
                        value={expression?.operands?.[1].value}
                        onChange={handleMultipleChange}
                        type={`${rhsType?.number ? 'number' : 'text'}`}
                        placeholder="Enter Something"
                        name="enter_text"
                        className="form-control"
                      />
                    </div>
                  </div>
                );
              }
              if (
                rhsType?.type == 'input' &&
                !rhsType?.multiple &&
                parameter?.value === '$payment.navigator_amount'
              ) {
                jsx = getAmountComp(
                  expression?.operands?.[1].value,
                  (e) =>
                    e.target.value && e.target.value >= 0 ? String(e.target.value * 100) : '',
                  valueType,
                );
              }
              if (
                rhsType?.type == 'input' &&
                rhsType?.number === false &&
                rhsType?.multiple === true
              ) {
                jsx = (
                  <div className="row">
                    <div className="col-xs-12">
                      <input
                        value={expression?.operands?.[1].value}
                        onChange={handleMultipleChange}
                        type="text"
                        placeholder="Enter comma separated text"
                        name="enter_text"
                        className="form-control"
                      />
                    </div>
                  </div>
                );
              }
              if (
                rhsType?.type == 'input' &&
                rhsType?.number === true &&
                rhsType?.multiple === true
              ) {
                jsx = (
                  <div className="row">
                    <div className="col-xs-12">
                      <input
                        value={expression?.operands?.[1].value}
                        onChange={handleBinNumberChange}
                        type="text"
                        placeholder="Enter comma separated numbers"
                        name="enter_text"
                        className={`form-control ${
                          shouldShowBinNumberErorr ? 'bin-input-error' : ''
                        }`}
                      />
                      {shouldShowBinNumberErorr && (
                        <div className="bin-input-error-msg">
                          Please enter valid BIN number of 4 to 6 digits.
                        </div>
                      )}
                    </div>
                  </div>
                );
              }
              if (rhsType?.between) {
                jsx = (
                  <div className="between-amount-div">
                    <div className="row">
                      <div className="col-xs-12" style={{ display: 'flex' }}>
                        <div className="between-inp-div" style={{ width: '45%' }}>
                          {getAmountComp(
                            expression?.operands?.[1].value.split(',')[0],
                            handleAmountBetweenChange(0),
                            valueType,
                          )}
                        </div>
                        <div
                          className="between-inp-div"
                          style={{ width: '10%', margin: '5px 6px' }}
                        >
                          <div className="text-center">to</div>
                        </div>
                        <div className="between-inp-div" style={{ width: '45%' }}>
                          {getAmountComp(
                            expression?.operands?.[1].value.split(',')[1],
                            handleAmountBetweenChange(1),
                            valueType,
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                );
              }
              return jsx;
            })()}
          </div>
        </div>
      )}
    </div>
  );
};
