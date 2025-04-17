import React from 'react';
import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';

const Wrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  background: #f9fcff;
  border: 1px solid #e3e8ea;
  padding: ${theme.spacing[5]}px ${theme.spacing[7]}px;
  display: flex;
  overflow: auto;
`,
);

const CustomRadio = styled.span(
  ({ active, theme }: { active: boolean; theme: Theme }) => `
  border: ${active ? 'none' : `1.5px solid ${theme.colors.surface.background.primary.intense}`};
  background: ${theme.colors.surface.background.primary.subtle};
  margin-right: ${theme.spacing[4]}px;
  display: flex;
  width: ${theme.spacing[5]}px;
  height: ${theme.spacing[5]}px;
  border-radius: 100%;
  align-items: center;
`,
);

const Tab = styled.button(
  ({ active, theme }: { active: boolean; theme: Theme }) => `
  background: ${theme.colors.surface.background.primary.subtle};
  border: 1px solid ${theme.colors.surface.background.primary.subtle};
  border-radius: ${theme.spacing[1]}px;
  margin-right: ${theme.spacing[6]}px;
  padding: ${theme.spacing[4]}px ${theme.spacing[5]}px;
  display: flex;
  align-items: center;
  color: ${theme.colors.surface.text.gray.normal};
  font-size: ${theme.typography.fonts.size[100]}px;
  flex-shrink: 0;

  .i-done {
    display: ${active ? 'inherit' : 'none'};
    color: ${theme.colors.surface.background.primary.intense};
  }

  ${
    active
      ? `
    border: 1px solid ${theme.colors.surface.background.primary.intense};
    position: relative;

    &:after {
      content: '\\25bc';
      position: absolute;
      top: 39px;
      color: ${theme.colors.surface.background.primary.intense};
      left: 45%;
    }
  `
      : ''
  }
`,
);
// TODO: We will add these comments back after we can get totalLength from backend
const PaymentsAndStorefrontTab = ({
  // totalPaymentPagesLength = 0,
  // totalStorefrontLength = 0,
  isStorefrontPage = false,
  setIsStorefrontPage,
}: {
  // totalPaymentPagesLength: number;
  // totalStorefrontLength: number;
  isStorefrontPage: boolean;
  setIsStorefrontPage: (val: boolean) => void;
}): JSX.Element => {
  return (
    <Wrapper>
      <Tab active={!isStorefrontPage} onClick={() => setIsStorefrontPage(false)}>
        <CustomRadio active={!isStorefrontPage}>
          <i className="i i-done" />
        </CustomRadio>
        {/* <b>{totalPaymentPagesLength}</b> &nbsp;  */}
        Payment Pages
      </Tab>

      <Tab
        active={isStorefrontPage}
        onClick={() => {
          track.storefrontCheckboxClicked();
          setIsStorefrontPage(true);
        }}
      >
        <CustomRadio active={isStorefrontPage}>
          <i className="i i-done" />
        </CustomRadio>
        {/* <b>{totalStorefrontLength}</b> &nbsp;  */}
        Razorpay Webstore
      </Tab>
    </Wrapper>
  );
};

export default PaymentsAndStorefrontTab;
