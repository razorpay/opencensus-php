import { CloseIcon, Heading } from '@razorpay/blade/components';
import { ModalLayoutInterface } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import React from 'react';
import { HeadingWrapper, IconWrapper, StyledLayout, StyledLayoutContent, TopBar } from './styled';

const ModalLayout = ({
  children,
  header,
  isCentered,
  closeModal,
  minHeight,
}: ModalLayoutInterface): JSX.Element => {
  return (
    <StyledLayout isCentered={isCentered} minHeight={minHeight}>
      <TopBar>
        <HeadingWrapper>
          {header?.title ? <Heading size="small">{header.title}</Heading> : null}
        </HeadingWrapper>
        {header?.close && (
          <IconWrapper onClick={closeModal}>
            <CloseIcon color="surface.action.icon.default.lowContrast" size="medium" />
          </IconWrapper>
        )}
      </TopBar>
      <StyledLayoutContent isCentered={isCentered}>{children}</StyledLayoutContent>
    </StyledLayout>
  );
};

export default ModalLayout;
