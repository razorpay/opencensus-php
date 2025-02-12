import React from 'react';
import { connect } from 'react-redux';

import { getValue, logical_operators } from './util';

class PreconditionPopover extends React.Component {
  render() {
    const OP =
      this.props.rule.precondition.type == 'logical'
        ? this.props.rule.precondition.operands
        : [this.props.rule.precondition];

    return (
      <div className="rule-body-popover">
        {OP.map((o, i) => (
          <React.Fragment key={i}>
            <div className="rule-body-popover-content">
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
    );
  }
}

export default connect((state) => {
  return state;
})(PreconditionPopover);
