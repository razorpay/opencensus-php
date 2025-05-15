import React from 'react';
import styled, { css } from 'styled-components';
import {
  Box,
  Radio,
  Text,
  RadioGroup,
  GlobeIcon,
  ShoppingBagIcon,
} from '@razorpay/blade/components';

const RadioContainer = styled.div<{ selected: boolean }>`
  padding: 12px;
  display: flex;
  gap: 12px;
  border: 1px solid rgb(229, 231, 235);
  flex: 1;
  border-radius: 6px;
  cursor: pointer;

  ${({ selected }) =>
    selected &&
    css`
      border-color: hsla(227, 100%, 59%, 1);
    `}
`;

export const RadioBlock = ({
  selected,
  onChange,
}: {
  selected: string;
  onChange: (value: string) => void;
}) => {
  return (
    <RadioGroup
      value={selected}
      onChange={({ value }) => {
        onChange(value);
      }}
    >
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          m: 'row',
        }}
        alignItems={{
          base: 'stretch',
          m: 'center',
        }}
        gap="spacing.4"
      >
        <RadioContainer selected={selected === 'shopify'} onClick={() => onChange('shopify')}>
          <Radio value="shopify"></Radio>
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Box display="flex" alignItems="center" gap="spacing.3">
              <ShoppingBagIcon color="interactive.icon.primary.normal" />
              <Text weight="semibold">I'm on shopify</Text>
            </Box>
            <Text size="small">Install via Shopify App Store</Text>
          </Box>
        </RadioContainer>

        <RadioContainer
          selected={selected === 'non-shopify'}
          onClick={() => onChange('non-shopify')}
        >
          <Radio value="non-shopify"></Radio>
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Box display="flex" alignItems="center" gap="spacing.3">
              <GlobeIcon color="interactive.icon.primary.normal" />
              <Text weight="semibold">I'm on another platform</Text>
            </Box>
            <Text size="small">Set up via manual script</Text>
          </Box>
        </RadioContainer>
      </Box>
    </RadioGroup>
  );
};
