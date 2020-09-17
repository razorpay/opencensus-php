import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  getValue,
  rule,
  mapRulesArrayToObject,
  getConditionOn,
  removeMid,
  logical_operators,
} from './util';
@connect((state) => {
  return state;
})
export default class PreconditionPopover extends React.Component {
  render() {
    const OP =
      this.props.rule.precondition.type == 'logical'
        ? this.props.rule.precondition.operands
        : [this.props.rule.precondition];

    return (
      <React.Fragment>
        <div class="rule-body-popover">
          {OP.map((o, i) => (
            <React.Fragment key={i}>
              <div class="rule-body-popover-content">
                <div>
                  {getValue('parameter', o.operands[0].value).name} is{' '}
                  {getValue('operator', o.value).name} {o.operands[1].value}
                </div>
              </div>
              {i < this.props.rule.precondition.operands.length - 1 &&
              this.props.rule.precondition.type === 'logical' ? (
                <div>
                  <b>
                    {
                      logical_operators.find((o) => o.value === this.props.rule.precondition.value)
                        .name
                    }
                  </b>
                </div>
              ) : null}
            </React.Fragment>
          ))}
        </div>
      </React.Fragment>
    );
  }
}
