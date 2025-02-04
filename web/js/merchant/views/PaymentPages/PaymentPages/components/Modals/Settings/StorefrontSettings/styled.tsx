import React from 'react';
import styled from 'styled-components';

import { ModalMask } from 'common/new-ui/Modal';
import { Theme } from '@razorpay/blade/components';
import Form from 'common/new-ui/Form';

const _ModalMask = ({ className = '', ...restProps }) => (
  <ModalMask unmodifiedClassName={className} {...restProps} />
);
const mobileView = '900px';
export const StyledModalMask = styled(_ModalMask)`
  .Modal-container {
    width: 400px;
    margin: 12px 0;
    overflow: visible;

    @media (max-width: 767px) {
      width: 100%;
      height: 100%;
      margin: 0;
      overflow: hidden;
    }
  }

  .Input {
    margin-bottom: 0;
  }

  .Input .Input-valueBefore + .Input-el {
    padding-left: ${(props) => (props.isSuccessScreen ? '66%' : '64%')};
    @media screen and (max-width: ${mobileView}) {
      padding-left: ${(props) => (props.isSuccessScreen ? '68%' : '66%')};
    }
  }

  .Input-content {
    margin-top: 4px;
  }

  .Input--expiryby .Input--Calendar .Input-content {
    margin-top: 6px;
  }
`;

export const StyledTitle = styled.div`
  font-weight: 600;
  padding: 16px 24px;
  font-size: 18px;
`;

export const CustomSlugSection = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: 0 ${theme.spacing[7]}px;
`,
);

export const SettingsSection = styled.div`
  padding: 24px;

  .Input {
    margin-top: 0;
  }

  &:not(:last-of-type) {
    border-bottom: 1px solid #ececec;
  }
`;

export const CtaSection = styled.div`
  display: flex;

  .body {
    flex: 1;
  }

  .action {
    margin-left: 5px;
    display: flex;
    align-items: flex-end;
  }
`;

export const Footer = styled.footer`
  padding: 16px 24px;
  background: #f6f6f6;
  border-top: 1px solid #e0e0e0;
  text-align: right;

  @media (max-width 767px) {
    position: fixed;
    bottom: 0;
    width: 100%;
    text-align: center;
    padding: ${({ theme }) => `${theme.spacing[8]}px ${theme.spacing[7]}px`};
  }

  .Button--transparent {
    padding: 8px 20px;
  }

  button:last-of-type {
    margin-right: 0;
  }
`;

export const StyledForm = styled(Form)`
  @media (max-width 767px) {
    height: 100vh;
  }

  .form-body {
    overflow: auto;
    height: 474px;
    background: linear-gradient(#fff 33%, rgba(255, 255, 255, 0)),
      linear-gradient(rgba(255, 255, 255, 0), #fff 66%) 0 100%,
      linear-gradient(rgba(209, 209, 209, 0.5), rgba(0, 0, 0, 0)),
      linear-gradient(rgba(0, 0, 0, 0), rgba(209, 209, 209, 0.5)) 0 100%;
    background-color: #fff;
    background-repeat: no-repeat;
    background-attachment: local, local, scroll, scroll;
    background-size: 100% 21px, 100% 21px, 100% 7px, 100% 7px;
  }

  @media (max-width 767px) {
    height: inherit;
    padding-bottom: 240px;
  }
`;
