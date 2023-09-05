import React from 'react';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';

const MethodType = ({ currentMethodType, selectedMethodType, handleMethodTypeChange }) => {
  return (
    <div data-testid="method-types">
      <label>{currentMethodType.name}</label>
      <div className="panel-actions">
        <BtnGroup
          className="panel-action-item"
          value={selectedMethodType}
          onChange={handleMethodTypeChange}
        >
          {currentMethodType?.types.map((type) =>
            type.shouldRender() ? (
              <Btn
                className="btn-default"
                value={type.value}
                key={type.value}
                data-testid={`${type.value}-btn-method-item`}
              >
                {type.name}
              </Btn>
            ) : null,
          )}
        </BtnGroup>
      </div>
    </div>
  );
};

export default MethodType;
