import React from 'react';
import Select from './Select';
export default class ProviderRow extends React.Component {
  handleLoadChange = (e) => {
    const load = e.target.value;
    const regx = /^[0-9]+$/;
    // Validate the input to allow only numbers between 0 and 100
    const isWithinRange = regx.test(load) && load >= 0 && load <= 100;

    if (load === '' || isWithinRange) {
      const { rule = {}, update } = this.props;
      const { additional_attribute = [] } = rule;

      update({
        ...rule,
        additional_attribute: [
          additional_attribute[0],
          {
            name: 'load',
            value: load,
          },
        ],
      });
    }
  };

  render() {
    const additional_attribute = this.props.rule.additional_attribute;
    return (
      <div
        className={`provider-expression-row ${
          this.props.readonly ? 'expression-row-readonly' : ''
        } ${this.props.dashed ? 'dashed if-tran-exp' : ''}`}
        onClick={(e) => {
          const parent = e.target;
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
                  onChange={this.handleLoadChange}
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
                searchable
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
                        ...this.props.rule.expression.operands.slice(1),
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
