import React from 'react';
import { MobileAppIcon, MonitorIcon, Theme } from '@razorpay/blade/components';
import styled from 'styled-components';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import PaymentPagesHeader from 'merchant/views/PaymentPages/PaymentPages/components/Header';

const tabletView = '1200px';
const mobileView = '900px';

type StyledProps = { theme: Theme };

export const StorefrontLeftWrapper = styled.div`
  width: 500px;
  background-color: #fff;
  border-right: 1px solid rgba(21, 102, 241, 0.18);
  overflow: auto;
  @media screen and (max-width: ${tabletView}) {
    width: 400px;
  }
  @media screen and (max-width: ${mobileView}) {
    width: 100%;
    padding-bottom: ${({ theme }: StyledProps) => theme.spacing[6]}px;
    height: 100%;
  }
`;
export const StorefrontRightWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  flex: 1;
  padding: ${theme.spacing[9]}px;
  overflow: auto;

  display: flex;
  flex-direction: column;
  align-items: center;

  & > h5 {
    width: 100%;
  }

  @media screen and (max-width: 1300px) {
    padding: ${theme.spacing[3]}px;
  }
`,
);

export const PageTitleWrapper = styled.div`
  min-height: 180px;
  padding: 35px 25px;
  background: linear-gradient(
    94.73deg,
    rgba(21, 102, 241, 0.16) 1.38%,
    rgba(21, 102, 241, 0.08) 89.1%,
    rgba(255, 255, 255, 0.08) 102.94%
  );

  border-bottom: 1px solid rgba(21, 102, 241, 0.18);
  position: relative;
`;

export const StoreFrontWrapper = styled.div`
  display: flex;
  color: #435775;
  height: calc(100vh - 50px); // header is 50px
  overflow: auto;
  @media screen and (max-width: ${mobileView}) {
    flex-direction: column;
    height: calc(100vh - 110px);
  }
`;

export const StoreFrontName = styled.div`
  background: linear-gradient(90deg, rgba(21, 102, 241, 0.08) 3.73%, rgba(21, 102, 241, 0) 97.01%);
  width: 200px;
  height: 34px;
  position: relative;
  padding-left: 24px;
  left: -24px;
  display: flex;
  align-items: center;
  font-weight: 600px;
`;

export const LeftContentWrapper = styled.div`
  width: calc(500px - 48px); // parent width - 2 x padding
  margin: 0 auto;
  @media screen and (max-width: ${tabletView}) {
    width: calc(400px - 48px);
  }
  @media screen and (max-width: ${mobileView}) {
    width: calc(100% - 48px);
  }
`;

export const AddProductBox = styled.div`
  text-align: center;
  background: #f8fafe;
  border: 1.6px dashed #2a86f3;
  border-radius: 2px;
  position: relative;
  bottom: 40px;
  height: 164px;
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  h4 {
    color: #1566f1;
    font-weight: 600;
  }
  h4,
  p {
    max-width: 250px;
  }
  .i-plus-circle {
    color: rgb(43, 133, 243);
    font-size: 28px;
    margin-bottom: -12px;
  }
`;

export const Iframe = styled.iframe(
  ({ isDesktopPreview, isMobile }: { isDesktopPreview?: boolean; isMobile?: boolean }) => `
  border: 0.6px solid rgba(121, 135, 156, 0.09);
  box-shadow: 0px 12.4019px 39.2727px rgba(21, 45, 75, 0.12),
    0px 0px 2.06699px rgba(21, 45, 75, 0.2);
  border-radius: 0 0 8px 8px;
  min-width: 800px;

  &.is-mobile {
    min-width: ${isDesktopPreview || isMobile ? '100%' : '400px'};
  }
`,
);

export const ProductsWrapper = styled.div`
  background: #ffffff;
  border-radius: 4px;
  padding: 16px;
  max-height: calc(100vh - 50px - 68px - 180px); // header, sticky footer, storefront title section
  overflow: auto;

  &.product-section {
    position: relative;
    top: -40px;
  }
`;

export const ProductItemsWrapper = styled.ul`
  list-style: none;
  padding: 0;
  margin: 0;
  & > li {
    display: flex;
    padding: 16px 0px 0px;
    justify-content: space-between;
  }

  .product-item-left,
  .product-item-right {
    display: flex;
    align-items: flex-start;
    // spacing for all direct children
    & > * {
      margin-right: 8px;
    }
  }

  .product-item-right.discount-applicable {
    p:first-child {
      text-decoration: line-through;
    }
  }
`;

export const OptionsDropdownWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: absolute;
  top: 28px;
  right: 0px;
  background: #FFFFFF;
  border: ${theme.colors.surface.border.gray.subtle};
  box-shadow: 0px 8px 12px rgb(21 45 75 / 10%), 0px 0px 1px rgb(21 45 75 / 20%);
  border-radius: ${theme.border.radius.small}px;
  padding: ${theme.spacing[4]}px;
  z-index: 15;
  width: 100px;
  & > div {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: ${theme.spacing[2]}px;
    color: ${theme.colors.interactive.text.primary.subtle};
    font-weight: ${theme.typography.fonts.weight.bold};
    padding-bottom: 6px;
    &.danger {
      color: ${theme.colors.feedback.text.negative.intense};
    }
    &:last-child {
      padding-bottom: 0px;
    }
  }
`,
);
export const StoreFrontentIcon = styled.img(
  ({ theme }: { theme: Theme }) => `
  padding-right: ${theme.spacing[2]}px;
`,
);

export const PageTitle = styled.p(
  ({ theme }: { theme: Theme }) => `
  font-size: ${theme.typography.fonts.size[500]}px;
  margin-top: ${theme.spacing[3]}px;
  display: flex;

  span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #132644;
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[500]}px;
    font-family: 'Lato';
  }
`,
);
export const EditPageTitleIcon = styled.img(
  ({ theme }: { theme: Theme }) => `
  padding-left: ${theme.spacing[2]}px;
  cursor: pointer;
`,
);

export const EditPageTitleWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  margin: ${theme.spacing[3]}px 0 ${theme.spacing[5]}px 0;
  background-color: ${theme.colors.surface.background.gray.intense};
  box-shadow:  0px 8px 12px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.2);
  border-radius: ${theme.border.radius.small}px;
  padding: ${theme.spacing[3]}px ${theme.spacing[4]}px;
`,
);

export const EditButtonWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-top: ${theme.spacing[2]}px;

  button {
    margin-left: ${theme.spacing[3]}px;
    width: ${theme.spacing[8]}px;
    height: ${theme.spacing[8]}px;
  }

`,
);
export const EditPageInputWrapper = styled.div`
  flex: 1;

  button {
    display: none;
  }
`;

// ************** Contact Details ********************
export const ContactDetailsWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: relative;
  margin-bottom: ${theme.spacing[3]}px;
  margin-left: ${theme.spacing[5]}px;
`,
);
export const ContactDetailsHeading = styled.p(
  ({ theme, showError }: { theme: Theme; showError: boolean }) => `
  color: ${
    showError ? theme.colors.feedback.text.negative.intense : theme.colors.surface.text.gray.subtle
  };
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[100]}px;
  line-height: ${theme.typography.lineHeights['2xl']}px;
  cursor: pointer;
  display: flex;
  align-items: center;

`,
);
export const ContactDetailsSubHeading = styled.p(
  ({ theme, showError }: { theme: Theme; showError: boolean }) => `
  color: ${
    showError ? theme.colors.feedback.text.negative.intense : theme.colors.surface.text.gray.muted
  };
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[75]}px;
  line-height: ${theme.typography.lineHeights[50]}px;
  display: flex;
  align-items: center;
`,
);

// ************** Contact Details ********************

// ************** Category dropdown ********************
export const CategoryDropdownWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin: ${theme.spacing[7]}px 0;
`,
);

export const Label = styled.p(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.gray.muted};
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[75]}px;
  line-height: ${theme.typography.lineHeights[50]}px;
  margin-bottom: ${theme.spacing[3]}px;
`,
);
export const Optional = styled.span(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.gray.disabled};
  font-style: italic;
  font-size: ${theme.typography.fonts.size[50]}px;
  font-weight: ${theme.typography.fonts.weight.regular};
  line-height: ${theme.typography.lineHeights[50]}px;
`,
);

export const AddCategory = styled.button(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.interactive.text.primary.normal};
  font-size: ${theme.typography.fonts.size[100]}px;
  font-weight: ${theme.typography.fonts.weight.bold};
  line-height: ${theme.typography.lineHeights[100]}px;
  background: none;
  border: none;
`,
);

export const ActiveCategory = styled.button(
  ({ theme, center }: { theme: Theme; center?: boolean }) => `
  background-color: ${theme.colors.surface.background.gray.moderate};
  font-size: ${theme.typography.fonts.size[100]}px;
  font-weight: ${theme.typography.fonts.weight.regular};
  line-height: ${theme.typography.lineHeights[100]}px;
  border: none;
  width: 100%;
  display: flex;
  justify-content: ${center ? 'center' : 'space-between'};
  align-items: center;
  height: 36px;
  color: ${theme.colors.surface.text.gray.subtle};
`,
);

export const DropdownWrapper = styled.div`
  position: relative;
`;

export const CategoryList = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: absolute;
  padding: 0;
  border: 1px solid ${theme.colors.interactive.border.gray.faded};
  top: 0;
  left: 0;
  right: 0;
  box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
  border-radius: ${theme.border.radius.small}px;

  z-index: 1;
`,
);

export const CategoryItemsWrapper = styled.ul(
  ({ theme }: { theme: Theme }) => `
  padding: 0;
  font-size: ${theme.typography.fonts.size[100]}px;
  font-weight: ${theme.typography.fonts.weight.regular};
  line-height: ${theme.typography.lineHeights[100]}px;
  max-height: ${theme.spacing[8] * 4}px;
  overflow: auto;
  margin-bottom: 0;
`,
);

export const CategoryItem = styled.li(
  ({
    theme,
    isAddButton,
    isActive,
  }: {
    theme: Theme;
    isAddButton?: boolean;
    isActive?: boolean;
  }) => `
  height: ${theme.spacing[8]}px;
  font-size: ${theme.typography.fonts.size[100]}px;
  font-weight: ${theme.typography.fonts.weight.regular};
  line-height: ${theme.typography.lineHeights[100]}px;
  color: ${
    isAddButton
      ? theme.colors.interactive.text.primary.normal
      : isActive
      ? theme.colors.surface.text.gray.subtle
      : 'rgba(22, 47, 86, 0.54)'
  };
  padding: ${theme.spacing[3]}px;
  cursor: pointer;
  list-style: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: ${
    isActive
      ? theme.colors.surface.background.gray.moderate
      : theme.colors.surface.background.gray.intense
  };
`,
);
// ************** Category dropdown ********************

export const DescriptionWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: space-between;
  width: 100%;
  align-items: center;
  margin-bottom: ${theme.spacing[7]}px;
`,
);

export const DescriptionLeftWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  flex-direction: column;

  & > p {
    padding-left: ${theme.spacing[2]}px;

    button div div {
      font-size: 0.75rem;
      &:focus, &:hover {
        font-size: 0.75rem;
      }
    }
  }
`,
);

const PreviewSizeWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  padding: ${theme.spacing[2]}px;
  justify-content: center;
  align-items: center;
  gap: ${theme.spacing[1]}px;
  border-radius: ${theme.border.radius.small}px;
  border: 1px solid ${theme.colors.interactive.border.gray.faded};
  background: ${theme.colors.surface.background.gray.intense};

  @media screen and (max-width: ${tabletView}) {
    display: none;
  }
`,
);

const PreviewImage = styled.div(
  ({ theme, isActive }: { theme: Theme; isActive: boolean }) => `
  background: ${
    isActive
      ? theme.colors.interactive.background.primary.faded
      : theme.colors.surface.background.gray.intense
  };
  border: 1px solid transparent;
  border-radius: ${theme.border.radius.small}px;
  width: 36px;
  height: 36px;
  padding: ${theme.spacing[3]}px;
  cursor: ${isActive ? 'not-allowed' : 'pointer'};
`,
);

export const PreviewButtons = ({
  isDesktop,
  onClick,
}: {
  isDesktop: boolean;
  onClick: (val: boolean) => void;
}): React.ReactElement => {
  return (
    <PreviewSizeWrapper>
      <PreviewImage isActive={!isDesktop} onClick={onClick.bind(null, false)}>
        <MobileAppIcon color="interactive.icon.primary.subtle" size="medium" />
      </PreviewImage>
      <PreviewImage isActive={isDesktop} onClick={onClick.bind(null, true)}>
        <MonitorIcon color="interactive.icon.primary.subtle" size="medium" />
      </PreviewImage>
    </PreviewSizeWrapper>
  );
};

export const SelectProductDrawerWrapper = styled(PaymentPagesDrawer)(
  ({ theme }: { theme: Theme }) => `
  .Modal-content {
    height: calc(100vh - 45px);
  }
  .Modal-body {
    display: flex;
    flex-direction: column;
    gap: ${theme.spacing[3]}px;
  }
`,
);

export const SelectCheckboxContainer = styled.div(
  ({ theme }) => `
  background: ${theme.colors.surface.background.gray.intense};
  border: 1px solid transparent;
  border-radius: ${theme.spacing[2]}px;
  padding: ${theme.spacing[7]}px;
  overflow: auto;
  height: calc(100vh - 45px - 120px - 75px);
  gap: 16px;
  display: flex;
  flex-direction: column;

  .Input {
    margin: 0;

    .Input-checkbox{
      margin-top: 5px;
      margin-right: 5px;
      margin-left: 2px;
    }
  }
`,
);
// export const SelectCheckboxContainer = styled.div(
//   ({ theme }) => `
// `,
// );

// CheckboxItem

// using 100vw as the blade component has many divs which aren't 100% width. Kept it 100% as we want it to be response on mobile
export const CheckboxContent = styled.div`
  display: flex;
  width: 100vw;
  max-width: 360px;
  justify-content: space-between;
  align-items: center;
`;

export const CheckboxLeftContent = styled.div(
  ({ theme }) => `
  display: flex;
  align-items: center;
  padding-left: ${theme.spacing[3]}px;
  & > img {
    width: 28px;
    height: 28px;
    margin-right: 16px;
  }
`,
);
export const CheckboxRightContent = styled.div<{ isDiscounted: boolean }>(
  ({ theme, isDiscounted }) => `
  display: flex;
  align-items: center;
  p:first-child {
    margin-right: ${isDiscounted ? `${theme.spacing[3]}px;` : '0px'};
    text-decoration: ${isDiscounted ? 'line-through' : 'none'};
  }
`,
);

export const AddFooterWrapper = styled.div(
  ({ theme }) => `
  display: flex;
  flex-direction: column;
  max-width: 200px;
  width: 100%;
  align-items: center;
  margin: 0 auto;
  > span {
    color: ${theme.colors.surface.text.gray.normal};
    margin-top: ${theme.spacing[3]}px;
    margin-bottom: ${theme.spacing[4]}px;
  }
`,
);

// MobileActionButtons

export const FooterWrapper = styled.div(
  ({ theme }) => `
  display: flex;
  position: fixed;
  bottom: 0px;
  background: white;
  box-shadow: 0px -8px 12px rgb(21 45 75 / 10%), 0px 0px 1px rgb(21 45 75 / 20%);
  width: 100%;
  left: 0px;
  padding: ${theme.spacing[5]}px ${theme.spacing[7]}px;
  gap: ${theme.spacing[5]}px;
  z-index: 5;
`,
);

export const StorefrontHeader = styled(PaymentPagesHeader)`
  & > div {
    min-width: 335px;
    background-color: ${({ theme }: StyledProps) => theme.colors.surface.background.gray.subtle};
    border-bottom: 1px solid ${({ theme }: StyledProps) => theme.colors.surface.border.gray.subtle};
    color: ${({ theme }: StyledProps) => theme.colors.surface.text.gray.normal};
  }
`;

// BottomSheet

export const BottomSheetWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin: 0px ${theme.spacing[7]}px ${theme.spacing[7]}px;
`,
);
export const BottomSheetItem = styled.div(
  ({ theme, gap }: { theme: Theme; gap?: string }) => `
  padding: ${theme.spacing[3]}px 0px;
  border-bottom: 1px solid #F0F0F0;
  display: flex;
  align-items: center;
  gap: ${gap ? gap : '0px'};

  .pp-danger-text {
    color: ${theme.colors.feedback.background.negative.intense};
  }
`,
);

export const StickyFooter = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: sticky;
  bottom: -${theme.spacing[5]}px;
  margin: 0 -${theme.spacing[5]}px -${theme.spacing[5]}px;
  padding: ${theme.spacing[5]}px;
  background: white;
  z-index: 10;
`,
);

export const CropWrapper = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  max-width: 984px;
  height: auto;
  max-height: 498px;
  position: relative;
`;
export const DragWrapper = styled.div<{
  theme: Theme;
}>(
  ({ theme }) => `
  border-radius: ${theme.border.radius.medium}px;
  width: 174px;
  padding: ${theme.spacing[3]}px ${theme.spacing[4]}px;
  align-items: flex-start;
  display: flex;
  gap: ${theme.spacing[3]}px;
  color: #fff;
  margin-bottom: ${theme.spacing[11]}px;
`,
);

export const DragWrapperDesktop = styled(DragWrapper)(
  ({ theme }) => `
  position: absolute;
  top: 105px;
  left: 50%;
  transform: translateX(-50%);
  background: ${theme.colors.feedback.background.neutral.intense};
  border: ${theme.border.width.thin}px solid ${theme.colors.feedback.border.neutral.intense};
  z-index: 2;
`,
);

export const DragWrapperMobile = styled(DragWrapper)(
  ({ theme }) => `
  background: ${theme.colors.feedback.background.information.intense};
  border: ${theme.border.width.thin}px solid ${theme.colors.feedback.border.information.intense};
`,
);
