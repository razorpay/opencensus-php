import React from 'react';
import styled from 'styled-components';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { Theme } from '@razorpay/blade/components';
export interface IDrawer {
  children: React.ReactNode;
  maskClosable?: boolean;
  onClose?: () => void;
  showCloseBtn?: boolean;
  position?: 'left' | 'right';
  footerButtons?: React.ReactNode;
  hasTransparentBackground?: boolean;
  isMobileCropper?: boolean;
  isStorefront?: boolean;
  top?: string;
}

const _Modal = ({ className = '', ...restProps }) => (
  <Modal unmodifiedClassName={className} {...restProps} />
);

const _ModalMask = ({ className = '', ...restProps }) => (
  <ModalMask unmodifiedClassName={className} {...restProps} />
);

const StyledModalMask = styled(_ModalMask)(
  ({ hasTransparentBackground }) => `
  ${
    hasTransparentBackground
      ? `
    background: transparent;
    `
      : ''
  }
  z-index: 10000;
`,
);

const DrawerWrapper = styled(_Modal)(
  ({
    position,
    top,
    isMobileCropper,
    isStorefront,
    theme,
  }: {
    position: string;
    top: string;
    isMobileCropper: boolean;
    isStorefront: boolean;
    theme: Theme;
  }) => `
  left: ${position === 'left' ? '0%' : 'unset'};
  right: ${position === 'right' ? '0%' : 'unset'};
  top: ${top};
  position: fixed;
  max-width: 500px;
  width: 100%;
  transform: none;
  height: ${isMobileCropper || isStorefront ? '100vh' : 'calc(100vh - 45px)'};
  padding: ${
    isMobileCropper
      ? `${theme.spacing[8]}px ${theme.spacing[0]}px`
      : isStorefront
      ? `${theme.spacing[8]}px ${theme.spacing[7]}px`
      : `${theme.spacing[8]}px ${theme.spacing[9]}px`
  };
  background-color: ${
    isMobileCropper
      ? theme.colors.interactive.icon.staticBlack.normal
      : theme.colors.interactive.icon.staticWhite.normal
  };
  box-shadow: 0px 10px 10px 1px #aaaaaa;
  z-index: 10000;
  .Modal-content {
    overflow: auto;
    margin-bottom: 100px;
    height: calc(100vh - 45px - 120px);
  }
  @media screen and (max-width: 768px){
    max-width: unset;
    width: 100%;
  }
`,
);

const ModalFooter = styled.div`
  height: 68px;
  background: #f8f9fb;
  border-top: 1px solid rgba(121, 135, 156, 0.09);
  position: absolute;
  bottom: 0px;
  width: calc(100% + 24px);
  left: -24px;
  padding: 16px 24px;
  text-align: right;
  & > button {
    margin-right: 16px;
    &:last-child {
      margin-right: 0px;
    }
  }
`;

const PaymentPagesDrawer = ({
  children,
  maskClosable,
  position = 'left',
  footerButtons,
  hasTransparentBackground = false,
  top = '45px',
  isMobileCropper = false,
  isStorefront = false,
  ...restProps
}: IDrawer): React.ReactElement => {
  return (
    <StyledModalMask
      maskClosable={maskClosable}
      hasTransparentBackground={hasTransparentBackground}
    >
      <DrawerWrapper
        position={position}
        top={top}
        isMobileCropper={isMobileCropper}
        isStorefront={isStorefront}
        {...restProps}
      >
        <ModalContent>{children}</ModalContent>
        {footerButtons && <ModalFooter>{footerButtons}</ModalFooter>}
      </DrawerWrapper>
    </StyledModalMask>
  );
};

export default PaymentPagesDrawer;
