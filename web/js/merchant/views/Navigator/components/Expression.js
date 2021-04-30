import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { Fragment } from 'react';
import Input, { Description, Label } from 'common/new-ui/Input';
import Field, {
  TextAreaField,
  SwitchField,
  SelectField,
  SearchableSelectField,
} from 'razorx/components/ui/Field';
import { PowerSelect } from 'react-power-select';
import InputField from 'common/ui/Forms/InputField';
import { titleCase } from 'common/utils/rzp-utils';
import Select from './Select';
import SelectConfig from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectConfig';
import { showNotification } from 'merchant_common/reducers/notifications';
import { AmountTooltip } from 'common/ui/Amount';
import { operators, getValue } from './util';
import { CSSTransition } from 'react-transition-group';

@connect(null, {
  showNotification,
})
export default class Expression extends React.Component {
  getValue = (type, value) => {
    let r;
    r = this.props.parameters;
    return r.find((p) => p.value == value) || '';
  };

  render() {
    const PARAMETER = this.getValue('parameter', this.props.expression.operands[0].value);
    const OPERATORS = operators.filter((o) => {
      let p = PARAMETER;
      if (p) {
        let ops = Object.keys(p.operators);
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
          let parent = e.target;
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
                <b>{this.props.expression.operands[1].value}</b>
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
                if (RHS_TYPE.type == 'input' && !RHS_TYPE.multiple) {
                  jsx = (
                    <div className="row">
                      <div className="col-xs-12">
                        <input
                          value={this.props.expression.operands[1].value}
                          onChange={(e) => {
                            let value;
                            value = e.target.value;
                            this.props.update({
                              ...this.props.expression,
                              operands: [
                                this.props.expression.operands[0],
                                {
                                  ...this.props.expression.operands[1],
                                  value: value,
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
                  RHS_TYPE.number === true &&
                  RHS_TYPE.multiple === true
                ) {
                  jsx = (
                    <div className="row">
                      <div className="col-xs-12">
                        <input
                          value={this.props.expression.operands[1].value}
                          onChange={(e) => {
                            let value;
                            value = e.target.value;
                            const values = value.split(',');
                            let update = true;
                            if (value[value.length - 1] === ',') {
                              values.forEach((item, index) => {
                                if (index < values.length - 1) {
                                  if (!(item.length >= 4 && item.length <= 6)) {
                                    update = false;
                                    this.props.showNotification({
                                      type: 'error',
                                      message: 'Please enter valid BIN number of 4 to 6 digits.',
                                    });
                                  }
                                }
                              });
                            }
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
                          class="form-control"
                        />
                      </div>
                    </div>
                  );
                }
                if (RHS_TYPE.between) {
                  jsx = (
                    <Fragment>
                      <div className="between-amount-div">
                        <div className="row">
                          <div className="col-xs-12" style={{ display: 'flex' }}>
                            <div className="between-inp-div" style={{ width: '45%' }}>
                              <div class="input-group">
                                <AmountTooltip
                                  currency={'INR'}
                                  parentQuerySelector=".ReactModal__Overlay .ReactModal__Content"
                                  customClass={`input-group-addon`}
                                />
                                <Field
                                  value={this.props.expression.operands[1].value.split(',')[0]}
                                  component={InputField}
                                  onChange={(e) => {
                                    let value = this.props.expression.operands[1].value.split(',');
                                    value[0] = e.target.value;
                                    value = value.join(',');
                                    this.props.update({
                                      ...this.props.expression,
                                      operands: [
                                        this.props.expression.operands[0],
                                        {
                                          ...this.props.expression.operands[1],
                                          value: value,
                                          type: VALUE_TYPE,
                                        },
                                      ],
                                    });
                                  }}
                                  type="number"
                                  placeholder="Enter Amount"
                                  name="amountInINR"
                                  class="form-control"
                                />
                              </div>
                            </div>
                            <div
                              className="between-inp-div"
                              style={{ width: '10%', margin: '5px 6px' }}
                            >
                              <div class="text-center">to</div>
                            </div>
                            <div className="between-inp-div" style={{ width: '45%' }}>
                              <div class="input-group">
                                <AmountTooltip
                                  currency={'INR'}
                                  parentQuerySelector=".ReactModal__Overlay .ReactModal__Content"
                                  customClass={`input-group-addon`}
                                />
                                <Field
                                  component={InputField}
                                  type="number"
                                  value={this.props.expression.operands[1].value.split(',')[1]}
                                  onChange={(e) => {
                                    let value = this.props.expression.operands[1].value.split(',');
                                    value[1] = e.target.value;
                                    value = value.join(',');
                                    this.props.update({
                                      ...this.props.expression,
                                      operands: [
                                        this.props.expression.operands[0],
                                        {
                                          ...this.props.expression.operands[1],
                                          value: value,
                                        },
                                      ],
                                    });
                                  }}
                                  placeholder="Enter Amount"
                                  name="amountInINR"
                                  class="form-control"
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </Fragment>
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
