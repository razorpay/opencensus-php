import React, { Fragment, useEffect, useRef } from 'react';
import * as dragula from 'react-dragula';

import Popover, { PopoverBody } from 'common/ui/Popover';

import { PreconditionPopover } from './PreconditionPopover';
import {
  getValue,
  mapRulesArrayToObject,
  removeMid,
  uniqueArray,
} from 'merchant/views/Optimizer/utils';
import { Operands, RuleGroup } from 'merchant/views/Optimizer/types';

interface RulesTableProps {
  ruleGroups: RuleGroup[];
  select?: (rule: RuleGroup) => void;
  onReorder?: (rules: RuleGroup[]) => void;
  radio?: boolean;
}

export const RulesTable = ({
  ruleGroups,
  select,
  onReorder,
  radio,
}: RulesTableProps): JSX.Element => {
  const dragulaTable = useRef<HTMLTableSectionElement>(null);
  useEffect(() => {
    if (onReorder) {
      let drake;
      const container = dragulaTable.current;
      if (container) {
        drake = dragula([container], {
          direction: 'vertical',
        });
        drake.on('drop', (e, s) => {
          const rules = Object.keys(s.children).map((k) => JSON.parse(s.children[k].id));
          if (onReorder) {
            rules.forEach((R, index) => {
              R.rules.forEach((r) => {
                r.score = index + 1;
              });
            });
            onReorder(rules);
          }
        });
      }
    }
  }, []);

  const is_highlighted = ruleGroups.find((ruleGroup) => ruleGroup.current);

  return (
    <Fragment>
      <div className="reorder-table">
        <div className="table table-responsive">
          <table className="table table-striped">
            <thead>
              <tr className="heading">
                {is_highlighted ? (
                  <th>
                    <div style={{ width: '20px' }} />
                  </th>
                ) : null}
                <th>Priority</th>
                <th>Rule Name</th>
                <th>Condition On</th>
                <th>Provider</th>
                {radio ? (
                  <th>
                    <div style={{ width: '10px' }} />
                  </th>
                ) : null}
              </tr>
            </thead>
            <tbody ref={dragulaTable}>
              {ruleGroups.map((ruleGroup, index) => {
                const operands =
                  ruleGroup.precondition.type == 'logical'
                    ? ruleGroup.precondition.operands
                    : [ruleGroup.precondition];
                const ruleGroupRules = mapRulesArrayToObject(ruleGroup.rules);
                return (
                  <Fragment key={index}>
                    <tr
                      className={`rule-row-table pointer${ruleGroup.current ? ' is_current' : ''}`}
                      style={{ cursor: 'pointer' }}
                      id={JSON.stringify(ruleGroup)}
                    >
                      {is_highlighted ? (
                        <td>
                          {ruleGroup.current ? (
                            <span>
                              <i
                                style={{ fontSize: '10px' }}
                                className="i i-burger-nav rule-table-burger-icn"
                              />
                              <Popover
                                theme="dark"
                                align="top"
                                parentQuerySelector={`.Modal--large`}
                              >
                                <PopoverBody>Drag and drop rule to set priority.</PopoverBody>
                              </Popover>
                            </span>
                          ) : null}
                        </td>
                      ) : null}
                      <td>{index + 1}</td>
                      <td>
                        <div className="rule-table-overflow">{removeMid(ruleGroup.name)}</div>
                      </td>
                      <td>
                        <div>
                          <div className="rule-table-overflow">
                            {uniqueArray(
                              (operands as Operands[]).map(
                                (operand) =>
                                  getValue('parameter', operand.operands?.[0].value).name,
                              ),
                            ).join(', ')}
                          </div>
                          <Popover
                            theme="dark"
                            align="bottom"
                            parentQuerySelector={`.Modal--large`}
                          >
                            <PopoverBody>
                              <PreconditionPopover ruleGroup={ruleGroup} />
                            </PopoverBody>
                          </Popover>
                        </div>
                      </td>
                      <td style={{ borderRight: ruleGroup.current ? '1px solid #2b83ea' : 'auto' }}>
                        <div className="rule-table-overflow">
                          {uniqueArray(
                            ruleGroup.rules.map(
                              (rule) => rule.expression.operands[0].operands[1].value,
                            ),
                          ).join(', ')}
                          <Popover
                            theme="dark"
                            align="bottom"
                            parentQuerySelector={`.Modal--large`}
                          >
                            <PopoverBody>
                              <div className="rule-body-popover">
                                <Fragment key={index}>
                                  <div className="rule-body-popover-content">
                                    {Object.keys(ruleGroupRules).map((key, i) => (
                                      <div style={{ marginTop: '15px' }} key={i}>
                                        <div className="rule-priority-heading">
                                          <b>Priority {key}</b>
                                        </div>
                                        {ruleGroupRules[key].map((item, index) => (
                                          <div className="rule-priority-body" key={index}>
                                            Route {item.additional_attribute[1].value}% Payment via{' '}
                                            {item.expression.operands[0].operands[1].value}
                                          </div>
                                        ))}
                                      </div>
                                    ))}
                                  </div>
                                </Fragment>
                              </div>
                            </PopoverBody>
                          </Popover>
                        </div>
                      </td>
                      {radio ? (
                        <td>
                          <input
                            name="rule_table"
                            type="radio"
                            onClick={() => {
                              if (select) {
                                select(ruleGroup);
                              }
                            }}
                          />
                        </td>
                      ) : null}
                    </tr>
                  </Fragment>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </Fragment>
  );
};
