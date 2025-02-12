import React from 'react';
import { CSSTransition } from 'react-transition-group';

import { deepClone } from 'common/utils/rzp-utils';
import { Operand } from 'merchant/views/Optimizer/Rules/models/Operand';
import { PreconditionModel } from 'merchant/views/Optimizer/Rules/models/PreconditionModel';
import {
  Precondition as PreconditionType,
  Parameter,
  Operands,
} from 'merchant/views/Optimizer/types';
import { LOGICAL_OPERATORS } from 'merchant/views/Optimizer/utils';

import { Expression } from './Expression';
import Select from './Select';

interface PreconditionProps {
  precondition: PreconditionType;
  update: (precondition: PreconditionType) => void;
  parameters: Parameter[];
  readonly: boolean;
  parent: string;
}

export const Precondition = ({
  precondition,
  update,
  parameters,
  readonly,
  parent,
}: PreconditionProps): JSX.Element => {
  const addCondition = () => {
    let newPrecondition = deepClone(precondition);
    if (newPrecondition.type == 'logical') {
      newPrecondition.operands.push({
        operands: [new Operand(), new Operand()],
        value: null,
        type: null,
      });
    } else {
      newPrecondition = {
        operands: [
          newPrecondition,
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

    update(newPrecondition);
  };

  const getExp = (expression, index = 9999) => {
    const onClose = () => {
      let newPrecondition = { ...precondition };
      if (newPrecondition.type === 'logical') {
        (newPrecondition?.operands as Operands[])?.splice(index, 1);
        if ((newPrecondition?.operands as Operands[])?.length == 1) {
          newPrecondition = newPrecondition.operands[0];
        }
      } else {
        newPrecondition = new PreconditionModel();
      }
      if (update) {
        setTimeout(() => {
          update(newPrecondition);
        }, 200);
      }
    };

    const onUpdate = (expression) => {
      let newPrecondition = { ...precondition };
      if (newPrecondition.type === 'logical') {
        newPrecondition.operands[index] = expression;
      } else {
        newPrecondition = expression as PreconditionType;
      }
      if (update) {
        update(newPrecondition);
      }
    };

    return (
      <Expression
        parameters={parameters}
        readonly={readonly}
        onClose={onClose}
        update={onUpdate}
        expression={expression}
      />
    );
  };

  const handlePreconditionChange = (values) => {
    const value = values.map((v) => v.value).join(',');
    const newPrecondition = { ...precondition };
    newPrecondition.value = value;
    if (update) {
      update(newPrecondition);
    }
  };

  return (
    <div className="precondition-div">
      <div className="row" style={{ position: 'relative' }}>
        {readonly && precondition.type == 'logical' ? (
          <div className={`col-xs-${parent === 'create-rule' ? 1 : 2}`} />
        ) : null}
        <div
          className={`col-xs-${
            readonly
              ? parent === 'create-rule'
                ? precondition.type == 'logical'
                  ? 11
                  : 12
                : 10
              : 10
          }`}
        >
          <div className="row">
            <div className="col-xs-12" style={{ paddingRight: 0 }}>
              {precondition.type == 'logical'
                ? (precondition?.operands as Operands[])?.map((p, index) => {
                    return getExp(p, index);
                  })
                : getExp(precondition)}
            </div>
          </div>
        </div>
        {!readonly ? (
          <div className="col-xs-2">
            {precondition.type == 'logical' ? (
              (precondition?.operands as Operands[])?.map((p, index) => {
                return (
                  <div
                    style={{ height: '54px' }}
                    className={precondition.type == 'logical' ? 'expression-row-end' : ''}
                    key={index}
                  />
                );
              })
            ) : (
              <div
                style={{ height: '54px' }}
                className={precondition.type == 'logical' ? 'expression-row-end' : ''}
              />
            )}
          </div>
        ) : null}
        {precondition.type == 'logical' && (precondition.operands as Operands[]).length > 1 ? (
          !readonly ? (
            <div className="operator-btn">
              <Select
                className="btn btn-primary operator-btn"
                selected={LOGICAL_OPERATORS.filter((o) => o.value == precondition.value)}
                placeholder="Operator"
                options={LOGICAL_OPERATORS}
                select={(values) => handlePreconditionChange(values)}
              />
            </div>
          ) : (
            <button type="button" className="btn operator-btn readonly">
              {LOGICAL_OPERATORS.find((o) => o.value == precondition.value)?.name}
            </button>
          )
        ) : null}
      </div>
      {!readonly ? (
        <CSSTransition in={true} exit={true} timeout={1000} classNames="slide-down">
          <div className="row">
            <div className="col-xs-12">
              <div onClick={addCondition} className="add-expression add-provider">
                <b className="pointer">Add Another Condition</b>
              </div>
            </div>
          </div>
        </CSSTransition>
      ) : null}
    </div>
  );
};
