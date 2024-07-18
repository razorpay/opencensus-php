import React from 'react';

import { getValue, LOGICAL_OPERATORS } from 'merchant/views/Optimizer/utils';
import { Operands, RuleGroup } from 'merchant/views/Optimizer/types';

interface PreconditionPopoverProps {
  ruleGroup: RuleGroup;
}

export const PreconditionPopover = ({ ruleGroup }: PreconditionPopoverProps): JSX.Element => {
  const operands =
    ruleGroup.precondition.type == 'logical'
      ? ruleGroup.precondition.operands
      : [ruleGroup.precondition];

  return (
    <div className="rule-body-popover">
      {(operands as Operands[]).map((operand, index) => (
        <React.Fragment key={index}>
          <div className="rule-body-popover-content">
            <div>
              {getValue('parameter', operand.operands?.[0].value).name} is{' '}
              {getValue('operator', operand.value).name} {operand.operands?.[1].value}
            </div>
          </div>
          {index < (ruleGroup.precondition.operands as Operands[]).length - 1 &&
          ruleGroup.precondition.type === 'logical' ? (
            <div>
              <b>
                {
                  LOGICAL_OPERATORS.find(
                    (operator) => operator.value === ruleGroup.precondition.value,
                  )?.name
                }
              </b>
            </div>
          ) : null}
        </React.Fragment>
      ))}
    </div>
  );
};
