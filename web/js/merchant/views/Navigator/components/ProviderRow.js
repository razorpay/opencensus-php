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

import { titleCase } from 'common/utils/rzp-utils';
import Select from './Select';
import SelectConfig from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectConfig';

export default class ProviderRow extends React.Component {
  render() {
    const additional_attribute = this.props.rule.additional_attribute;
    return (
      <div
        className={`provider-expression-row ${
          this.props.readonly ? 'expression-row-readonly' : ''
        } ${this.props.dashed ? 'dashed if-tran-exp' : ''}`}
        onClick={(e) => {
          let parent = e.target;
          if (parent.classList[0] === 'provider-expression-row') {
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
                Route <b>{additional_attribute[1] && additional_attribute[1].value}%</b> Payments
                via{' '}
                <b>
                  {/* filter the providers based on the value stored in rule and show the name instead of the value which contains ID */}
                  {this.props.providers.filter(
                    (p) => p.value === this.props.rule.expression.operands[0].operands[1].value,
                  )[0]
                    ? this.props.providers.filter(
                        (p) => p.value === this.props.rule.expression.operands[0].operands[1].value,
                      )[0].name
                    : this.props.rule.expression.operands[0].operands[1].value}
                </b>
              </div>
            </div>
          </div>
        ) : (
          <div style={{ display: 'flex' }}>
            <div style={{ width: '10%' }}>
              <span class="mid-text">Route</span>
            </div>
            <div style={{ width: '20%' }}>
              <div class="input-group">
                <input
                  type="text"
                  value={additional_attribute[1] && additional_attribute[1].value}
                  class="form-control"
                  onChange={(e) => {
                    const load = e.target.value;
                    this.props.update({
                      ...this.props.rule,
                      additional_attribute: [
                        additional_attribute[0],
                        {
                          name: 'load',
                          value: load,
                        },
                      ],
                    });
                  }}
                />
                <span class="input-group-addon">%</span>
              </div>
            </div>
            <div style={{ width: '15%' }}>
              <span class="mid-text">payment via</span>
            </div>
            <div style={{ width: '20%' }} class="select-provider">
              <Select
                placeholder="Select Provider"
                options={this.props.providers}
                selected={this.props.rule.expression.operands[0].operands[1].value
                  .split(',')
                  .map((v) => this.props.providers.find((p) => p.value == v))
                  .filter((i) => i)}
                select={(values) => {
                  values = values.map((v) => v.value).join(',');
                  this.props.update({
                    ...this.props.rule,
                    expression: {
                      ...this.props.rule.expression,
                      operands: [
                        {
                          ...this.props.rule.expression.operands[0],
                          operands: [
                            this.props.rule.expression.operands[0].operands[0],
                            {
                              type: 'string',
                              value: values,
                              operands: null,
                            },
                          ],
                        },
                        this.props.rule.expression.operands[1],
                      ],
                    },
                  });
                }}
              />
            </div>
          </div>
        )}
      </div>
    );
  }
}

export const PROVIDERS = [
  { name: 'Smart Router1', id: 1, value: 'smartrouter' },
  { name: 'Razorpay', id: 2, value: 'razorpay' },
  { name: 'Smart Router2', id: 3, value: 'smartrouter2' },
];
