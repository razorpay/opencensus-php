import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';

export const TooltipContainer = styled(View)`
  position: relative;
`;

export const InputInfo = styled(View)`
  max-width: 250px;
  min-width: 200px;
  display: inline-block;
  position: absolute;
  margin-left: 12px;
  background: ${({ theme }) => getColor(theme, 'darkBlue.200')};
  padding: 12px 16px;
  color: ${({ theme }) => getColor(theme, 'white.800')};
  border-radius: 2px;
  font-size: 12px;
  top: 50%;
  transform: translateY(-50%);
  opacity: 1;
  pointer-events: none;
  transition: 0.2s;
  overflow-wrap: break-word;
  z-index: 2;

  ul {
    padding: 0;
    list-style: none;

    li:not(:last-child) {
      margin-bottom: 4px;
    }
  }

  &:before {
    border: 8px solid transparent;
    border-right-color: ${({ theme }) => getColor(theme, 'darkBlue.200')};
    content: '';
    position: absolute;
    right: 100%;
    top: 50%;
    margin-top: -8px;
  }
`;
