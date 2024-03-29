import React from 'react';
import SwitchField from 'common/ui/Forms/SwitchField';
import { MainSwitch, SwitchWrapper } from './styled';
import { SwitchPropsType } from './types';
import { Text } from 'merchant_common/views/Reports/components';

export const Switch = ({ label, value, onChange }: SwitchPropsType): JSX.Element => {
  return (
    <SwitchWrapper>
      <Text size="medium" variant="body" weight="semibold" color="surface.text.gray.subtle">
        {label}
      </Text>
      {/* when onChange is passed to SwitchField, it's not called when clicked via userEvent in test  
          so as to make sure it works, placing a onClick handler here
      */}
      <MainSwitch onClick={() => onChange(!value)} aria-label={`${label ?? ''} Switch`}>
        <SwitchField type="prime round" checked={value} />
      </MainSwitch>
    </SwitchWrapper>
  );
};
