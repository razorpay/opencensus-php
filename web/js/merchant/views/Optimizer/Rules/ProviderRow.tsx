import React from 'react';

import { Rule, MappedProiders } from 'merchant/views/Optimizer/types';

import Select from './Select';

interface ProviderRowProps {
  rule: Rule;
  update?: (rule: Rule) => void;
  providers: MappedProiders[];
  readonly: boolean;
  dashed?: boolean;
  onClose?: () => void;
}

export const ProviderRow = ({
  rule,
  update,
  providers,
  readonly,
  dashed,
  onClose,
}: ProviderRowProps) => {
  const handleLoadChange = (e) => {
    const load = e.target.value;
    const regx = /^[0-9]+$/;
    // Validate the input to allow only numbers between 0 and 100
    const isWithinRange = regx.test(load) && load >= 0 && load <= 100;

    if (load === '' || isWithinRange) {
      const { additional_attribute = [] } = rule;

      if (update) {
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
    }
  };

  const selectProvider = (values: MappedProiders[]) => {
    const valueString = values.map((v) => v.value).join(',');
    if (update) {
      update({
        ...rule,
        expression: {
          ...rule.expression,
          operands: [
            {
              ...rule.expression.operands[0],
              operands: [
                rule.expression.operands[0].operands[0],
                {
                  type: 'string',
                  value: valueString,
                  operands: null,
                },
              ],
            },
            ...rule.expression.operands.slice(1),
          ],
        },
      });
    }
  };

  const additional_attribute = rule.additional_attribute;

  return (
    <div
      className={`provider-expression-row ${readonly ? 'expression-row-readonly' : ''} ${
        dashed ? 'dashed if-tran-exp' : ''
      }`}
      onClick={(e) => {
        const parent = e.target as HTMLElement;
        if (parent.classList[0] === 'provider-expression-row') {
          if (onClose) {
            onClose();
          }
        }
      }}
    >
      {readonly ? (
        <div className="row">
          <div className="col-xs-12">
            <div className="expression-readonly-high">
              Route <b>{additional_attribute[1] && additional_attribute[1].value}%</b> Payments via{' '}
              <b>
                {/* filter the providers based on the value stored in rule and show the name instead of the value which contains ID */}
                {providers.filter(
                  (p) => p.value === rule.expression.operands[0].operands[1].value,
                )[0]
                  ? providers.filter(
                      (p) => p.value === rule.expression.operands[0].operands[1].value,
                    )[0].name
                  : rule.expression.operands[0].operands[1].value}
              </b>
            </div>
          </div>
        </div>
      ) : (
        <div style={{ display: 'flex' }}>
          <div style={{ width: '10%' }}>
            <span className="mid-text">Route</span>
          </div>
          <div style={{ width: '20%' }}>
            <div className="input-group">
              <input
                type="text"
                value={additional_attribute[1] && additional_attribute[1].value}
                className="form-control"
                onChange={handleLoadChange}
              />
              <span className="input-group-addon">%</span>
            </div>
          </div>
          <div style={{ width: '15%' }}>
            <span className="mid-text">payment via</span>
          </div>
          <div style={{ width: '20%' }} className="select-provider">
            <Select
              placeholder="Select Provider"
              options={providers}
              searchable
              selected={
                rule.expression.operands[0].operands[1].value
                  .split(',')
                  .map((v) => providers.find((p) => p.value == v))
                  .filter((i) => i) as MappedProiders[]
              }
              select={(values) => selectProvider(values as MappedProiders[])}
            />
          </div>
        </div>
      )}
    </div>
  );
};
