import React from 'react';
import {
  Box,
  Text,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import { PAYLATER_LABELS } from '../constants';

const PaylatersMultiSelect = (props) => {
  const { paylaterOptions, paylaterSelected, changeGatewayPaylaters, disabled, isFormEdit } = props;

  return (
    <Box display="flex" alignItems="center">
      <Box minWidth="180px">
        <Text>Paylaters</Text>
      </Box>
      {isFormEdit ? (
        <Box minWidth="280px">
          <Dropdown selectionType="multiple" testID="paylater-select">
            <SelectInput
              placeholder="Select Paylaters"
              name="paylater"
              value={paylaterSelected}
              isDisabled={disabled}
              onChange={changeGatewayPaylaters}
            />
            <DropdownOverlay>
              <ActionList>
                {paylaterOptions.map((paylater) => (
                  <ActionListItem
                    key={paylater}
                    title={PAYLATER_LABELS[paylater] ?? paylater}
                    value={paylater}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      ) : (
        <Text weight="semibold">
          {paylaterSelected?.map((paylater) => PAYLATER_LABELS[paylater])?.join(', ')}
        </Text>
      )}
    </Box>
  );
};

export default PaylatersMultiSelect;
