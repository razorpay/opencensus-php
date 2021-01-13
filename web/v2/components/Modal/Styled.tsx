import React from 'react';
import styled from 'styled-components';
import Text from '@razorpay/blade/src/atoms/Text';
import { getColor } from '@razorpay/blade/src/_helpers/theme';

export const DialogContainer = styled.div`
  position: fixed;
  right: 0;
  bottom: 0;
  top: 0;
  left: 0;
  display: flex;
  align-items: ${({ $bottomSheet }) => ($bottomSheet ? 'flex-end' : 'center')};
  justify-content: center;
  width: 100%;
  min-height: 100%;
  background-color: ${({ theme }) => getColor(theme, 'shade.950')};
  touch-action: 'none';
  opacity: ${({ $opacity }) => $opacity};
  z-index: 200000;
`;

export const Dialog = styled.div`
  position: relative;
  background-color: ${({ theme }) => getColor(theme, 'background.100')};
  margin-left: ${({ theme }) => theme.spacings.medium};
  margin-top: ${({ theme }) => theme.spacings.medium};
  margin-right: ${({ theme }) => theme.spacings.medium};
  margin-bottom: ${({ theme }) => theme.spacings.medium};
  border-radius: ${({ theme }) => theme.spacings.xsmall};
  max-height: 100%;
  overflow: auto;
  max-width: 100%;
  width: 500px;
  opacity: ${({ $opacity }) => $opacity};
  transform: ${({ $y }) => `translateY(${$y}px)`};
  &:focus {
    outline: 'none';
  }
`;
// Button from blade not attaching className to DOM, hence using container
export const CloseIconContainer = styled.div`
  position: absolute;
  top: ${({ theme }) => theme.spacings.small};
  right: ${({ theme }) => theme.spacings.small};
  display: flex;
  align-items: center;
  justify-content: center;
`;

export const BottomSheet = styled.div.attrs((props) => ({
  style: {
    opacity: props.$opacity,
    transform: `translateY(${props.$y}%)`,
  },
}))`
  position: relative;
  background-color: ${({ theme }) => getColor(theme, 'background.100')};
  max-width: 100%;
  max-height: 90%;
  height: ${({ $bottomSheetHeight }) => $bottomSheetHeight};
  overflow: auto;
  width: 100%;
  border-top-left-radius: ${({ theme }) => theme.spacings.small};
  border-top-right-radius: ${({ theme }) => theme.spacings.small};
  box-shadow: 0px -4px 15px ${({ theme }) => getColor(theme, 'sapphire.930')};

  &:focus {
    outline: 'none';
  }
`;

export const BottomSheetHandle = styled.div`
  height: ${({ theme }) => theme.spacings.xsmall};
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
  border-radius: ${({ theme }) => theme.spacings.xsmall};
  margin-top: ${({ theme }) => theme.spacings.small};
  width: 64px;
  margin-left: auto;
  margin-right: auto;
`;

const HeaderText = styled(Text)`
  margin-top: ${({ theme }) => theme.spacings.xxxlarge};
  margin-bottom: ${({ theme }) => theme.spacings.large};
  margin-left: ${({ theme }) => theme.spacings.xxlarge};
  margin-right: ${({ theme }) => theme.spacings.xxxlarge};
`;
export const ModalHeader: React.FC = (props) => <HeaderText weight="bold" {...props} />;

const BodyText = styled(Text)`
  margin-top: ${({ theme }) => theme.spacings.large};
  margin-bottom: ${({ theme }) => theme.spacings.large};
  margin-left: ${({ theme }) => theme.spacings.xxlarge};
  margin-right: ${({ theme }) => theme.spacings.xxlarge};
`;
export const ModalBody: React.FC = (props) => <BodyText size="medium" {...props} />;

export const FooterText = styled(Text)`
  margin-top: ${({ theme }) => theme.spacings.large};
  margin-left: ${({ theme }) => theme.spacings.xxlarge};
  margin-right: ${({ theme }) => theme.spacings.xxlarge};
  padding-top: ${({ theme }) => theme.spacings.large};
  padding-bottom: ${({ theme }) => theme.spacings.large};
  display: flex;
  justify-content: flex-end;
`;

export const ModalFooter: React.FC = (props) => (
  <FooterText size="medium" align="right" {...props} />
);

export const BottomSheetTextHeader = styled(Text)`
  position: sticky;
  top: 0px;
  z-index: 1;
  background: ${({ theme }) => getColor(theme, 'white.900')};
`;
