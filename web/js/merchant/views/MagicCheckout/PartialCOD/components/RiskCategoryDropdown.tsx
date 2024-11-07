import React, { useEffect, useState } from 'react';
import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
} from '@razorpay/blade/components';
import {
  CUSTOMER_RISK_CATEGORY,
  CustomerRiskCategory,
  RiskCategoryDropdownProps,
} from 'merchant/views/MagicCheckout/PartialCOD/types';

const RiskCategoryDropdown = ({
  value = [],
  onChange,
  label = '',
  labelPosition,
  validationState,
}: RiskCategoryDropdownProps) => {
  const [activeRiskCategories, setActiveRiskCategories] =
    useState<(CustomerRiskCategory | CUSTOMER_RISK_CATEGORY.ALL)[]>(value);

  useEffect(() => {
    if (value.length === 3) setActiveRiskCategories([...value, CUSTOMER_RISK_CATEGORY.ALL]);
    else setActiveRiskCategories(value);
  }, [value]);

  const handleOnChange = ({ values }) => {
    let newRiskCategories;
    if (
      values.includes(CUSTOMER_RISK_CATEGORY.ALL) &&
      !activeRiskCategories.includes(CUSTOMER_RISK_CATEGORY.ALL)
    ) {
      //Check if ALL is selected
      newRiskCategories = [
        CUSTOMER_RISK_CATEGORY.LOW,
        CUSTOMER_RISK_CATEGORY.MEDIUM,
        CUSTOMER_RISK_CATEGORY.HIGH,
      ];
    } else if (values.length !== 4 && values.includes(CUSTOMER_RISK_CATEGORY.ALL)) {
      // If ALL is selected and other values are deselected
      newRiskCategories = values.filter((value) => value !== CUSTOMER_RISK_CATEGORY.ALL);
    } else if (activeRiskCategories.includes(CUSTOMER_RISK_CATEGORY.ALL)) {
      //Check if ALL is deselected
      newRiskCategories = [];
    } else {
      newRiskCategories = values;
    }
    onChange(newRiskCategories);
    setActiveRiskCategories(newRiskCategories);
  };

  return (
    <Dropdown selectionType="multiple">
      <SelectInput
        label={label}
        placeholder="Select Risk Categories"
        name="riskCategory"
        labelPosition={labelPosition}
        onChange={handleOnChange}
        value={activeRiskCategories}
        errorText="Select at least one risk category"
        validationState={validationState}
      />
      <DropdownOverlay>
        <ActionList>
          <ActionListItem title="Include All Buyers" value={CUSTOMER_RISK_CATEGORY.ALL} />
          <ActionListItem title="Low Risk Buyers" value={CUSTOMER_RISK_CATEGORY.LOW} />
          <ActionListItem title="Medium Risk Buyers" value={CUSTOMER_RISK_CATEGORY.MEDIUM} />
          <ActionListItem title="High Risk Buyers" value={CUSTOMER_RISK_CATEGORY.HIGH} />
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};

export default RiskCategoryDropdown;
