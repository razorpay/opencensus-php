import React from 'react';
import Field from 'razorx/components/ui/Field';
import InputField from 'common/ui/Forms/InputField';
import Select from './Select';
import Amount, { AmountTooltip } from 'common/ui/Amount';
import { operators, getValue } from './util';

export default class Expression extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      showBinNumberErorr: false,
    };
  }

  getValue = (value) => {
    const r = this.props.parameters;
    return r.find((p) => p.value == value) || '';
  };

  renderAmount = (value) => {
    if (value) {
      return value.split(',').map((item, key, arr) => (
        <>
          <Amount key={`${item}_${key}`} value={item} />
          {key !== arr.length - 1 && <span>, </span>}
        </>
      ));
    }
    return null;
  };

  getAmountComp = (val, handleChange, VALUE_TYPE) => (
    <div class="input-group">
      <AmountTooltip
        currency="INR"
        parentQuerySelector=".ReactModal__Overlay .ReactModal__Content"
        customClass="input-group-addon"
      />
      <Field
        value={val ? val / 100 : val}
        component={InputField}
        onChange={(e) => {
          const value = handleChange(e);
          this.props.update({
            ...this.props.expression,
            operands: [
              this.props.expression.operands[0],
              {
                ...this.props.expression.operands[1],
                value,
                type: VALUE_TYPE,
              },
            ],
          });
        }}
        type="number"
        placeholder="Enter Amount"
        name="amountInINR"
        class="form-control"
        min="0"
      />
    </div>
  );

  render() {
    const PARAMETER = this.getValue(this.props.expression.operands[0].value);
    const OPERATORS = operators.filter((o) => {
      const p = PARAMETER;
      if (p) {
        const ops = Object.keys(p.operators);
        return ops.indexOf(o.value) !== -1;
      } else {
        return true;
      }
    });
    const VALUES = PARAMETER
      ? PARAMETER.values.map((p) => {
          return {
            id: p.value,
            name: p.value,
            value: p.value,
            disabled_message: p.disabled_message,
            disabled: p.disabled,
          };
        })
      : [];
    const RHS_TYPE =
      this.props.expression.operands[0].value && this.props.expression.value
        ? PARAMETER.operators[this.props.expression.value]
        : {};
    let VALUE_TYPE;
    if (PARAMETER) {
      VALUE_TYPE = PARAMETER.type;
      if (this.props.expression.value == 'in' || this.props.expression.value == 'between') {
        VALUE_TYPE = 'array';
      }
    }
    return (
      <div
        className={`expression-row ${this.props.readonly ? 'expression-row-readonly' : ''}`}
        onClick={(e) => {
          e.stopPropagation();
          const parent = e.target;
          if (parent.classList[0] === 'expression-row') {
            if (this.props.onClose) {
              this.props.onClose();
            }
          }
        }}
      >
        {this.props.readonly ? (
          <div className="row">
            <div className="col-xs-12">
              <div class="expression-readonly-high">
                When <b>{PARAMETER.name}</b>{' '}
                {getValue('operator', this.props.expression.value).name}{' '}
                {PARAMETER.value === '$payment.navigator_amount' ? (
                  this.renderAmount(this.props.expression.operands[1].value)
                ) : (
                  <b>{this.props.expression.operands[1].value}</b>
                )}
              </div>
            </div>
          </div>
        ) : (
          <div style={{ display: 'flex' }}>
            <div style={{ width: '12%' }}>
              <span class="mid-text">When</span>
            </div>
            <div style={{ width: '24%' }} className="p0 select-parameter">
              <Select
                placeholder="Select Parameter"
                options={this.props.parameters}
                selected={this.props.expression.operands[0].value
                  .split(',')
                  .map((v) => this.props.parameters.find((p) => p.value == v))
                  .filter((i) => i)}
                select={(values) => {
                  values = values.map((v) => v.value).join(',');
                  this.props.update({
                    ...this.props.expression,
                    operands: [
                      { ...this.props.expression.operands[0], value: values },
                      this.props.expression.operands[1],
                    ],
                    type: null,
                    value: null,
                  });
                }}
              />
            </div>
            <div style={{ width: '3%' }}>
              <span class="mid-text">is</span>
            </div>
            <div style={{ width: '30%' }} className="col-xs-2 select-operator">
              <Select
                placeholder="Select Connection"
                options={OPERATORS}
                selected={operators.filter((o) => {
                  return o.value == this.props.expression.value;
                })}
                select={(values) => {
                  this.props.update({
                    ...this.props.expression,
                    value: values[0].value,
                    type: getValue('operator', values[0].value).type,
                    operands: [
                      {
                        ...this.props.expression.operands[0],
                        type: 'variable',
                      },
                      {
                        ...this.props.expression.operands[1],
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
                    multiple={RHS_TYPE.multiple}
                    placeholder="Select Comparing Value"
                    options={VALUES}
                    selected={this.props.expression.operands[1].value
                      .split(',')
                      .map((v) => VALUES.find((p) => p.value == v))
                      .filter((i) => i)}
                    select={(values) => {
                      values = values.map((v) => v.value).join(',');
                      this.props.update({
                        ...this.props.expression,
                        operands: [
                          {
                            ...this.props.expression.operands[0],
                            type: 'variable',
                          },
                          {
                            ...this.props.expression.operands[1],
                            value: values,
                            type: VALUE_TYPE,
                          },
                        ],
                      });
                    }}
                  />
                );
                if (
                  RHS_TYPE.type == 'input' &&
                  !RHS_TYPE.multiple &&
                  PARAMETER.value !== '$payment.navigator_amount'
                ) {
                  jsx = (
                    <div className="row">
                      <div className="col-xs-12">
                        <input
                          value={this.props.expression.operands[1].value}
                          onChange={(e) => {
                            const value = e.target.value;
                            this.props.update({
                              ...this.props.expression,
                              operands: [
                                this.props.expression.operands[0],
                                {
                                  ...this.props.expression.operands[1],
                                  value,
                                  type: VALUE_TYPE,
                                },
                              ],
                            });
                          }}
                          type={`${RHS_TYPE.number ? 'number' : 'text'}`}
                          placeholder="Enter Something"
                          name="enter_text"
                          class="form-control"
                        />
                      </div>
                    </div>
                  );
                }
                if (
                  RHS_TYPE.type == 'input' &&
                  !RHS_TYPE.multiple &&
                  PARAMETER.value === '$payment.navigator_amount'
                ) {
                  jsx = this.getAmountComp(
                    this.props.expression.operands[1].value,
                    (e) =>
                      e.target.value && e.target.value >= 0 ? String(e.target.value * 100) : '',
                    VALUE_TYPE,
                  );
                }
                if (
                  RHS_TYPE.type == 'input' &&
                  RHS_TYPE.number === false &&
                  RHS_TYPE.multiple === true
                ) {
                  jsx = (
                    <div className="row">
                      <div className="col-xs-12">
                        <input
                          value={this.props.expression.operands[1].value}
                          onChange={(e) => {
                            const value = e.target.value;
                            this.props.update({
                              ...this.props.expression,
                              operands: [
                                this.props.expression.operands[0],
                                {
                                  ...this.props.expression.operands[1],
                                  value,
                                  type: VALUE_TYPE,
                                },
                              ],
                            });
                          }}
                          type="text"
                          placeholder="Enter comma separated text"
                          name="enter_text"
                          class="form-control"
                        />
                      </div>
                    </div>
                  );
                }
                if (
                  RHS_TYPE.type == 'input' &&
                  RHS_TYPE.number === true &&
                  RHS_TYPE.multiple === true
                ) {
                  jsx = (
                    <div className="row">
                      <div className="col-xs-12">
                        <input
                          value={this.props.expression.operands[1].value}
                          onChange={(e) => {
                            const value = e.target.value;
                            const values = value.split(',');
                            let update = true;
                            values.forEach((item, index) => {
                              if (index < values.length - 1) {
                                if (!(item.length >= 4 && item.length <= 6)) {
                                  update = false;
                                  if (!this.state.showBinNumberErorr) {
                                    this.setState({ showBinNumberErorr: true });
                                  }
                                }
                              }
                              if (
                                this.state.showBinNumberErorr &&
                                item.length >= 4 &&
                                item.length <= 6
                              ) {
                                this.setState({ showBinNumberErorr: false });
                              }
                            });
                            const arr = values.map((item, index) => {
                              const res = item.trim();
                              if (isNaN(res) || (res === '' && index !== values.length - 1)) {
                                update = false;
                              }
                              return res;
                            });
                            if (update) {
                              const newVal = arr.map((item) => {
                                if (item !== '') {
                                  return item;
                                }
                                return null;
                              });
                              this.props.update({
                                ...this.props.expression,
                                operands: [
                                  this.props.expression.operands[0],
                                  {
                                    ...this.props.expression.operands[1],
                                    value: newVal.join(','),
                                    type: VALUE_TYPE,
                                  },
                                ],
                              });
                            }
                          }}
                          type="text"
                          placeholder="Enter comma separated numbers"
                          name="enter_text"
                          class={`form-control ${
                            this.state.showBinNumberErorr ? 'bin-input-error' : ''
                          }`}
                        />
                        {this.state.showBinNumberErorr && (
                          <div className="bin-input-error-msg">
                            Please enter valid BIN number of 4 to 6 digits.
                          </div>
                        )}
                      </div>
                    </div>
                  );
                }
                if (RHS_TYPE.between) {
                  jsx = (
                    <div className="between-amount-div">
                      <div className="row">
                        <div className="col-xs-12" style={{ display: 'flex' }}>
                          <div className="between-inp-div" style={{ width: '45%' }}>
                            {this.getAmountComp(
                              this.props.expression.operands[1].value.split(',')[0],
                              (e) => {
                                let value = this.props.expression.operands[1].value.split(',');
                                value[0] =
                                  e.target.value && e.target.value >= 0 ? e.target.value * 100 : '';
                                value = value.join(',');
                                return value;
                              },
                              VALUE_TYPE,
                            )}
                          </div>
                          <div
                            className="between-inp-div"
                            style={{ width: '10%', margin: '5px 6px' }}
                          >
                            <div class="text-center">to</div>
                          </div>
                          <div className="between-inp-div" style={{ width: '45%' }}>
                            {this.getAmountComp(
                              this.props.expression.operands[1].value.split(',')[1],
                              (e) => {
                                let value = this.props.expression.operands[1].value.split(',');
                                value[1] =
                                  e.target.value && e.target.value >= 0 ? e.target.value * 100 : '';
                                value = value.join(',');
                                return value;
                              },
                              VALUE_TYPE,
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
  }
}
