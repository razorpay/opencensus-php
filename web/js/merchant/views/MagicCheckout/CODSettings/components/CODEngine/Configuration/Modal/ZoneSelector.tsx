import React from 'react';

import { Box, Checkbox, Text, ChevronDownIcon } from '@razorpay/blade/components';
import FeeRuleSelector from './FeeRuleSelector';
import {
  ActionSelector,
  Seperator,
  ZoneLabel,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/styled';

const ZoneSelector = ({
  zone,
  selectedRuleIds,
  setSelectedRuleIds,
  openId,
  setOpenId,
  isCODBlocked,
}: {
  zone: Record<string, unknown>;
  selectedRuleIds: string[];
  setSelectedRuleIds: (ids: string[] | Record<string, string[]>) => void;
  openId: string;
  setOpenId: (id: string) => void;
  isCODBlocked: boolean;
}): JSX.Element => {
  const zoneId = zone.id as string;
  const isOpen = openId === zoneId;
  const isSelected = selectedRuleIds[zoneId]?.length > 0;

  const toggleCollapse = () => {
    if (isOpen) setOpenId('');
    else setOpenId(zoneId as string);
  };

  const modifiedSetSelectedRuleIds = (ids) => {
    if (!selectedRuleIds[zoneId]) {
      selectedRuleIds[zoneId] = [];
    }
    selectedRuleIds[zoneId] = [...ids];
    setSelectedRuleIds({ ...selectedRuleIds });
  };

  const handleChange = ({ isChecked }) => {
    if (isChecked) {
      toggleCollapse();
    } else {
      delete selectedRuleIds[zoneId];
      setSelectedRuleIds({ ...selectedRuleIds });
    }
  };

  return (
    <ActionSelector key={zoneId} forCategory>
      <Box display="flex" alignItems="center">
        <Checkbox isChecked={isSelected} onChange={handleChange} isDisabled={isCODBlocked}>
          <span />
        </Checkbox>
        <ZoneLabel onClick={toggleCollapse}>
          <Text>
            {zone.name} - India <Seperator>|</Seperator>
            {zone.state_count} states
          </Text>
          <div className={`chevron-icon-${isOpen ? 'up' : 'down'}`}>
            <ChevronDownIcon size="medium" color="currentColor" />
          </div>
        </ZoneLabel>
      </Box>
      {isOpen ? (
        <Box marginTop="spacing.5" paddingLeft="spacing.5">
          <FeeRuleSelector
            selectedRuleIds={selectedRuleIds[zoneId] || []}
            setSelectedRuleIds={modifiedSetSelectedRuleIds}
            isCODBlocked={isCODBlocked}
          />
        </Box>
      ) : null}
    </ActionSelector>
  );
};

export default ZoneSelector;
