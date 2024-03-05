import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

import { getBackgroundColor, getStatusIconColor } from './utils';

interface BackgroundProps {
  status: string;
  step: number;
}

export const StyledIconBackground = styled.div<BackgroundProps>`
  height: 20px;
  width: 20px;
  position: relative;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: ${({ status, step, theme }: BackgroundProps & { theme: Theme }) =>
    getBackgroundColor(status, step, theme)};
`;

export const StyledBoxMetedata = styled.div`
  min-height: 44px;
  width: 300px;
  top: 20px;
  left: -20px;
  border-left: 1px solid
    ${({ theme }: { theme: Theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
  padding-left: 30px;
  stroke-width: 1px;
  width: 100%;
`;

export const StyledText = styled.p`
  color: ${({ theme }: { theme: Theme }) => `${theme.colors.surface.text.normal.lowContrast}`};
  font-size: 14px;
  font-weight: ${({ theme }: { theme: Theme }) => `${theme.typography.fonts.weight.bold}`};
`;

export const StyledTextContainer = styled.div`
  position: absolute;
  left: 30px;
  top: -26px;
  display: flex;
  flex-direction: row;
  width: 100%;
`;

export const StyledBox = styled.div`
  div:first-child {
    div:nth-child(2) > div:last-child {
      border: none;
    }
  }
  width: 100%;
`;

export const StyledTimeline = styled.div`
  display: flex;
  align-items: flex-start;
  gap: ${({ theme }: { theme: Theme }) => `${theme.spacing[4]}px`};
  padding: 15px 16px 15px 24px;
  width: 330px;
  overflow: hidden;

  @media screen and (min-width: 420px) and (max-width: 1450px) {
    width: 300px;
  }
  @media screen and (min-width: 370px) and (max-width: 420px) {
    width: 285px;
  }
  @media screen and (min-width: 300px) and (max-width: 370px) {
    width: 250px;
  }
  @media screen and (max-width: 300px) {
    width: 190px;
  }
`;

export const StyledTimelineContainer = styled.div`
  display: flex;
  flex-direction: column;
  width: 100%;
`;

export const StyledStatusIcon = styled.div<{ status: string; step: number }>`
  height: 10px;
  width: 10px;
  position: relative;
  border-radius: 50%;
  background-color: ${({ status, step, theme }: BackgroundProps & { theme: Theme }) =>
    getStatusIconColor(status, step, theme)};
  display: flex;
  justify-content: center;
  align-items: center;
`;
