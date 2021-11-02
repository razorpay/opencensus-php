import React from 'react';
import { deepClone } from 'common/utils/rzp-utils';
import Select from './Select';
import Expression from './Expression';
import { isExpressionValid, logical_operators } from './util';
import { Operand } from '../models/Operand';
import { PreconditionModel } from '../models/PreconditionModel';
import { CSSTransition } from 'react-transition-group';

export default class Precondition extends React.Component {
  addCondition = () => {
    let precondition = deepClone(this.props.precondition);
    if (precondition.type == 'logical') {
      precondition.operands.push({
        operands: [new Operand(), new Operand()],
        value: null,
        type: null,
      });
    } else {
      precondition = {
        operands: [
          precondition,
          {
            operands: [new Operand(), new Operand()],
            value: null,
            type: null,
          },
        ],
        value: '&&',
        type: 'logical',
      };
    }
    // eslint-disable-next-line react/no-unused-state
    this.setState({ precondition }, () => {
      this.props.update(precondition);
    });
  };

  getExp = (expression, index = 9999) => {
    return (
      <Expression
        precondition={this.props.precondition}
        valid={isExpressionValid(expression)}
        key={index}
        parameters={this.props.parameters}
        readonly={this.props.readonly}
        onClose={() => {
          let precondition = { ...this.props.precondition };
          if (precondition.type === 'logical') {
            precondition.operands.splice(index, 1);
            if (precondition.operands.length == 1) {
              precondition = precondition.operands[0];
            }
          } else {
            precondition = new PreconditionModel();
          }
          if (this.props.update) {
            setTimeout(() => {
              this.props.update(precondition);
            }, 200);
          }
        }}
        // eslint-disable-next-line no-shadow
        update={(expression) => {
          let precondition = { ...this.props.precondition };
          if (precondition.type === 'logical') {
            precondition.operands[index] = expression;
          } else {
            precondition = expression;
          }
          if (this.props.update) {
            this.props.update(precondition);
          }
        }}
        expression={expression}
      />
    );
  };

  render() {
    return (
      <div class="precondition-div">
        <div class="row" style={{ position: 'relative' }}>
          {this.props.readonly && this.props.precondition.type == 'logical' ? (
            <div className={`col-xs-${this.props.parent === 'create-rule' ? 1 : 2}`} />
          ) : null}
          <div
            className={`col-xs-${
              this.props.readonly
                ? this.props.parent === 'create-rule'
                  ? this.props.precondition.type == 'logical'
                    ? 11
                    : 12
                  : 10
                : 10
            }`}
          >
            <div className="row">
              <div className="col-xs-12" style={{ paddingRight: 0, paddingLeft: 0 }}>
                {this.props.precondition.type == 'logical'
                  ? this.props.precondition.operands.map((p, index) => {
                      return this.getExp(p, index);
                    })
                  : this.getExp(this.props.precondition)}
              </div>
            </div>
          </div>
          {!this.props.readonly ? (
            <div className="col-xs-2">
              {this.props.precondition.type == 'logical' ? (
                this.props.precondition.operands.map((p, index) => {
                  return (
                    <div
                      style={{ height: '54px' }}
                      class={this.props.precondition.type == 'logical' ? 'expression-row-end' : ''}
                      key={index}
                    />
                  );
                })
              ) : (
                <div
                  style={{ height: '54px' }}
                  class={this.props.precondition.type == 'logical' ? 'expression-row-end' : ''}
                />
              )}
            </div>
          ) : null}
          {this.props.precondition.type == 'logical' &&
          this.props.precondition.operands.length > 1 ? (
            !this.props.readonly ? (
              <div class="operator-btn">
                <Select
                  class="btn btn-primary operator-btn"
                  type="button"
                  selected={logical_operators.filter(
                    (o) => o.value == this.props.precondition.value,
                  )}
                  placeholder="Operator"
                  options={logical_operators}
                  select={(values) => {
                    const value = values.map((v) => v.value).join(',');
                    const precondition = { ...this.props.precondition };
                    precondition.value = value;
                    if (this.props.update) {
                      this.props.update(precondition);
                    }
                  }}
                />
              </div>
            ) : (
              <button
                style={{ left: this.props.readonly ? 'auto' : '35px' }}
                className={`btn btn-primary operator-btn readonly`}
              >
                {logical_operators.find((o) => o.value == this.props.precondition.value).name}
              </button>
            )
          ) : null}
        </div>
        {!this.props.readonly ? (
          <CSSTransition in={true} exit={true} timeout={1000} classNames="slide-down">
            <div class="row">
              <div className="col-xs-12">
                <div
                  onClick={this.addCondition}
                  class="add-expression"
                  style={{ color: '#2B83EA', marginTop: '18px' }}
                >
                  <b class="pointer">Add Another Condition</b>
                </div>
              </div>
            </div>
          </CSSTransition>
        ) : null}
      </div>
    );
  }
}
