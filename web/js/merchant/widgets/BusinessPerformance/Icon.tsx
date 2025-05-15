import { ArrowDownIcon, ArrowUpIcon, IconComponent } from '@razorpay/blade/components';
import React from 'react';
import styled from 'styled-components';

import { PerformanceComponent } from './types';

export type IconProps = {
  name: string;
  variant: PerformanceComponent['variant'];
};

const IconMap: Record<string, IconComponent> = {
  arrow_up: ArrowUpIcon,
  arrow_down: ArrowDownIcon,
};

const ColoredBox = styled.div(({ theme, variant }) => {
  return `
    background-color: ${theme.colors.feedback.background[variant].intense};
    border-radius: ${theme.border.radius.round};
    display: grid;
    height: ${theme.spacing[7]}px;
    place-items: center;
    width: ${theme.spacing[7]}px;
  `;
});

export function Icon({ name, variant }: IconProps) {
  const IconComponent = IconMap[name];

  if (!IconComponent) {
    return null;
  }

  return (
    <ColoredBox variant={variant}>
      <IconComponent color="surface.icon.staticWhite.normal" />
    </ColoredBox>
  );
}
