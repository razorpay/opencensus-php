import React from 'react';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import {
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  ListIcon,
} from '@razorpay/blade/components';

const RecurringType = ({ currentMethodType, selectedRecurringType, handleRecurringTypeChange }) => {
  const { viewType } = currentMethodType;
  if (viewType === 'btn-group') {
    return (
      <div data-testid="btn-types">
        <label>{currentMethodType.recurringFilterName}</label>
        <div className="panel-actions">
          <BtnGroup
            className="panel-action-item"
            value={selectedRecurringType}
            onChange={handleRecurringTypeChange}
          >
            {currentMethodType?.recurringTypes.map((type) =>
              type.shouldRender() ? (
                <Btn
                  className="btn-default"
                  value={type.value}
                  key={type.value}
                  data-testid={`${type.value}-btn-item`}
                >
                  {type.name}
                </Btn>
              ) : null,
            )}
          </BtnGroup>
        </div>
      </div>
    );
  } else {
    return (
      <div data-testid="dropdown-types">
        <Dropdown>
          <SelectInput
            label={currentMethodType.recurringFilterName}
            name="recurringTypes"
            placeholder="Creation and Auto Debit"
            onChange={({ values }) => values?.[0] && handleRecurringTypeChange(values[0])}
            icon={ListIcon}
            value={selectedRecurringType}
          />
          <DropdownOverlay>
            <ActionList testID="recurring-types-container">
              {currentMethodType?.recurringTypes.map(({ name, value, shouldRender }) =>
                shouldRender() ? (
                  <ActionListItem
                    key={name}
                    title={name}
                    value={value}
                    testID={`${value}-drop-down-item`}
                  />
                ) : null,
              )}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </div>
    );
  }
};

export default RecurringType;
