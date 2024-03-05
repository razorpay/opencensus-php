import React from 'react';
import styled from 'styled-components';

const screenXS = '767px';

export interface IPaymentPagesHeader {
  title: string | React.ReactElement;
  actionBtns: React.ReactElement;
  handleClose: () => void;
  children: React.ReactElement;
  isSticky?: boolean;
}

const PageNavContainer = styled.div<{ isSticky?: boolean }>(
  ({ isSticky }) => `
  position: ${isSticky ? 'fixed' : 'unset'};
  z-index: ${isSticky ? '3' : 'unset'};
  width: ${isSticky ? '100%' : 'unset'};
`,
);

const PageTitle = styled.div`
  font-size: 18px;
  font-weight: bold;
  padding-left: 8px;

  span {
    opacity: 0.35;
    font-weight: 400;
    word-break: break-all;
  }
`;

const PageAction = styled.div`
  display: flex;
  justify-content: flex-end;
  margin-right: 34px;

  & > button {
    line-height: 10px;
    padding: 10px 8px;
    // height: 32px;
    vertical-align: middle;
    margin-left: 10px;
    font-weight: 700;
    display: flex;
    justify-content: center;
    align-items: center;

    @media (max-width: ${screenXS}) {
      & > span {
        display: none;
      }
    }

    i {
      margin-right: 4px;

      @media (max-width: ${screenXS}) {
        margin-right: 0;
        font-size: 20px;
      }
    }
  }

  .Button--header {
    background: rgba(11, 112, 231, 0.05);
    border: 1px solid #a2c9f6;
    box-sizing: border-box;
    border-radius: 2px;
    padding: 8px 16px !important;
  }

  & > .magic-link {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 5px 16px !important;
  }

  .Button--transparent {
    padding: 10px 0;
  }

  .new-label {
    background: #29c24d;
    border-radius: 10px;
    font-weight: bold;
    font-size: 12px;
    padding: 4px 10px;
    margin-left: 8px;
  }

  .mobile-cta-container {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    min-width: 100vw;
    justify-content: center;
    padding: 12px;
    background: #fff;
    box-shadow: 0 2px 24px 0 rgba(0, 0, 0, 0.24);

    @media (max-width: ${screenXS}) {
      display: flex;
    }

    .Button {
      width: 48%;
    }
  }
`;

const PageSizeContainer = styled.div`
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
`;

const CloseButton = styled.span`
  font-size: 24px;
  line-height: 14px;
  position: absolute;
  right: 4px;
  color: #fff;
  cursor: pointer;
  padding: 4px;

  @media (min-width 320px) and (max-width 360px) {
    right: -8px;
  }

  &:hover {
    color: #fff;
    opacity: 0.7;
  }
`;

const PageNav = styled.div`
  color: #fff;
  background-color: #2f3445;
  box-shadow: 0 2px 24px 0 rgba(0, 0, 0, 0.24);
  padding: 9px 12px;
  min-width: 854px;

  @media (max-width: ${screenXS}) {
    min-width: 100vw;
  }
`;

// displays page title, action buttons & close button in a navbar. Wraps the children alongside the navbar
// hides the actions buttons if the buttons depend on any API
const PaymentPagesHeader = ({
  title,
  actionBtns,
  handleClose,
  children,
  isSticky,
  ...restProps
}: IPaymentPagesHeader): React.ReactElement => {
  return (
    <PageNavContainer className="page-nav-container" isSticky={isSticky} {...restProps}>
      <PageNav>
        <PageSizeContainer>
          <PageTitle>{title}</PageTitle>

          {actionBtns && <PageAction>{actionBtns}</PageAction>}

          <CloseButton data-testid="close-btn" onClick={handleClose}>
            ×
          </CloseButton>
        </PageSizeContainer>
      </PageNav>
      {children}
    </PageNavContainer>
  );
};

export default PaymentPagesHeader;
