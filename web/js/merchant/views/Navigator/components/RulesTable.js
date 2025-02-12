/* eslint-disable */
import { Component, Fragment } from 'react';
import * as dragula from 'react-dragula';
import ModalHeader from 'common/ui/ModalHeader';
import PreconditionPopover from './PreconditionPopover';
import {
  getValue,
  rule,
  mapRulesArrayToObject,
  getConditionOn,
  removeMid,
  logical_operators,
  uniqueArray,
  parameters,
} from './util';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { deepClone } from 'common/utils/rzp-utils';

export class RulesTable extends Component {
  state = {
    isLoading: true,
    clicked: {},
  };

  componentDidMount() {
    if (this.props.onReorder) {
      let drake;
      const container = document.getElementById('dragula');
      if (container) {
        drake = dragula([container], {
          direction: 'vertical',
        });
        drake.on('drop', (e, s) => {
          let rules;
          rules = Object.keys(s.children).map((k) => JSON.parse(s.children[k].id));
          if (this.props.onReorder) {
            rules.forEach((R, index) => {
              R.rules.forEach((r) => {
                r.score = index + 1;
              });
            });
            this.props.onReorder(rules);
          }
        });
      }
    }
  }

  render() {
    const rules = deepClone(this.props.rules);
    const is_highlighted = this.props.rules.find((r) => r.current);
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
                  {this.props.radio ? (
                    <th>
                      <div style={{ width: '10px' }} />
                    </th>
                  ) : null}
                </tr>
              </thead>
              <tbody id="dragula">
                {this.props.rules.map((r, i) => {
                  const OP =
                    r.precondition.type == 'logical' ? r.precondition.operands : [r.precondition];
                  const RULES = mapRulesArrayToObject(r.rules);
                  return (
                    <Fragment key={i}>
                      <tr
                        className="rule-row-table pointer"
                        style={{ cursor: 'pointer' }}
                        className={r.current ? 'is_current' : ''}
                        id={JSON.stringify(r)}
                      >
                        {is_highlighted ? (
                          <td>
                            {r.current ? (
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

                        <td>{i + 1}</td>
                        {/* <td>{r.rules[0].score}</td> */}
                        <td>
                          <div className="rule-table-overflow">{removeMid(r.name)}</div>
                        </td>
                        <td>
                          <div>
                            <div className="rule-table-overflow">
                              {uniqueArray(
                                OP.map((o) => getValue('parameter', o.operands[0].value).name),
                              ).join(', ')}
                            </div>
                            <Popover
                              theme="dark"
                              align="bottom"
                              parentQuerySelector={`.Modal--large`}
                            >
                              <PopoverBody>
                                <PreconditionPopover parameters={parameters} rule={r} />
                              </PopoverBody>
                            </Popover>
                          </div>
                        </td>
                        <td style={{ borderRight: r.current ? '1px solid #2b83ea' : 'auto' }}>
                          <div className="rule-table-overflow">
                            {uniqueArray(
                              r.rules.map((i) => i.expression.operands[0].operands[1].value),
                            ).join(', ')}
                            <Popover
                              theme="dark"
                              align="bottom"
                              parentQuerySelector={`.Modal--large`}
                            >
                              <PopoverBody>
                                <div className="rule-body-popover">
                                  <Fragment key={i}>
                                    <div className="rule-body-popover-content">
                                      {Object.keys(RULES).map((k, i) => (
                                        <div style={{ marginTop: '15px' }} key={i}>
                                          <div className="rule-priority-heading">
                                            <b>Priority {k}</b>
                                          </div>
                                          {RULES[k].map((o) => (
                                            <div className="rule-priority-body">
                                              Route {o.additional_attribute[1].value}% Payment via{' '}
                                              {o.expression.operands[0].operands[1].value}
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
                        {this.props.radio ? (
                          <td>
                            <input
                              name="rule_table"
                              type="radio"
                              onClick={() => {
                                if (this.props.select) {
                                  this.props.select(r);
                                }
                              }}
                            />
                          </td>
                        ) : null}
                        {/* <td>{moment(r.updatedAt).format('DD/MM/YYYY')}</td> */}
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
  }
}
