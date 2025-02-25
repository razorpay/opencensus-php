import React from 'react';
import { Box, CopyIcon, Divider, IconButton, Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import { IPaymentDetails, PaymentStatus } from './types';
import { getAmountColor } from './utils';
import CustomClipboard from '@libs/web-nexus/common/ui/Clipboard/Custom';

export const getOverviewBgColor = (theme: Theme, status: IPaymentDetails['status']): string => {
  switch (status) {
    case PaymentStatus.CREATED:
      return theme.colors.feedback.background.notice.subtle;
    case PaymentStatus.AUTHENTICATED:
    case PaymentStatus.AUTHORIZED:
      return theme.colors.feedback.background.neutral.subtle;
    case PaymentStatus.CAPTURED:
      return theme.colors.feedback.background.positive.subtle;
    case PaymentStatus.FAILED:
      return theme.colors.feedback.background.notice.subtle;
    case PaymentStatus.REFUNDED:
    default:
      return theme.colors.feedback.background.information.subtle;
  }
};

export const OverviewIconWrapper = styled.div`
  display: inline-flex;
  border-radius: ${({ theme }: { theme: Theme }) => `${theme.spacing[2]}px`};
`;

export const OverviewSubtextWrapper = styled.div(
  ({ isMobile, theme }: { isMobile: boolean; theme: Theme }) => `
  text-align: ${isMobile ? 'center' : 'left'};
  & > p {
    display: inline-flex;
    gap: ${theme.spacing[1]}px;
  }
`,
);

const DashedDividerWrapper = styled.div`
  & > div {
    border-style: dashed;
  }
`;

export const DashedDivider = (): JSX.Element => (
  <DashedDividerWrapper>
    <Divider dividerStyle="dashed" />
  </DashedDividerWrapper>
);

export const SectionFooter = styled.div(
  ({ theme }: { theme: Theme }) => `
  background-color: ${theme.colors.surface.background.gray.moderate};
  padding: ${theme.spacing[4]}px ${theme.spacing[5]}px;
  border-left: 1px solid ${theme.colors.surface.border.gray.muted};
  border-bottom: 1px solid ${theme.colors.surface.border.gray.muted};
  border-right: 1px solid ${theme.colors.surface.border.gray.muted};
  border-bottom-right-radius: ${({ theme }: { theme: Theme }) => `${theme.spacing[2]}px`};
  border-bottom-left-radius: ${({ theme }: { theme: Theme }) => `${theme.spacing[2]}px`};
`,
);

export const CollapsibleContainer = styled.div`
  cursor: pointer;
`;

type SectionHeaderProps = {
  theme: Theme;
  enableBorderBottomRadius?: boolean;
};

export const SectionHeader = styled.div<SectionHeaderProps>(
  ({ theme, enableBorderBottomRadius = false }) => `
  background-color: ${theme.colors.surface.background.gray.moderate};
  padding: ${theme.spacing[5]}px ${theme.spacing[6]}px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top-left-radius: ${theme.border.radius.medium}px;
  border-top-right-radius: ${theme.border.radius.medium}px;
  border-top: 1px solid ${theme.colors.surface.border.gray.muted};
  border-left: 1px solid ${theme.colors.surface.border.gray.muted};
  border-right: 1px solid ${theme.colors.surface.border.gray.muted};
  border-bottom: 1px solid ${theme.colors.surface.border.gray.muted};

  border-bottom-right-radius: ${
    enableBorderBottomRadius ? `${theme.border.radius.medium}px` : `${theme.border.radius.none}px`
  };
  border-bottom-left-radius: ${
    enableBorderBottomRadius ? `${theme.border.radius.medium}px` : `${theme.border.radius.none}px`
  };
`,
);
export const RowWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  padding: ${theme.spacing[4]}px 0px;
  & > p:first-child {
    min-width: 200px;
    position:relative;
    span{
      position:absolute;
      top:-1px;
      padding-left: 2px;
    }
  }
  flex-direction: column;
  @media screen and (min-width: ${theme.breakpoints.m}px) {
    flex-direction: row;
  };
`,
);

export const RowsWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: 0px ${theme.spacing[2]}px;
  & > div:first-child {
    padding-top: 0;
  }
  & > div:last-child {
    padding-bottom: 0;
  }
`,
);

export interface ICardWrapper {
  theme: Theme;
  enableBorderTopRadius?: boolean;
  enableBorderBottomRadius?: boolean;
}

export const CardWrapper = styled.div<{
  enableBorderBottomRadius?: boolean;
  enableBorderTopRadius?: boolean;
}>`
  > div {
    > div {
      border-radius: 0;
      ${({ enableBorderTopRadius, theme }: { enableBorderTopRadius?: boolean; theme: Theme }) =>
        enableBorderTopRadius &&
        `
        border-top-left-radius:  ${theme.spacing[2]}px;
        border-top-right-radius:  ${theme.spacing[2]}px;
  `}
      ${({
        enableBorderBottomRadius,
        theme,
      }: {
        enableBorderBottomRadius?: boolean;
        theme: Theme;
      }) =>
        enableBorderBottomRadius &&
        `
        border-bottom-left-radius: ${theme.spacing[2]}px;
        border-bottom-right-radius:  ${theme.spacing[2]}px;
  `}
    }
  }
`;

export const BoxContainer = styled.div(
  ({ disableMarginTop, isRefund }: { disableMarginTop?: boolean; isRefund?: boolean }) => `
  margin-top: ${!disableMarginTop ? '-10px' : isRefund ? '8px' : '4px'};
`,
);

const CopyIconWrapper = styled.span(
  ({ theme }: { theme: Theme }) => `
  & svg path {
    fill: ${theme.colors.interactive.icon.primary.subtle}
  }
`,
);

export const CopyButton = ({ onClick }: { onClick: () => void }): JSX.Element => (
  <CopyIconWrapper>
    <IconButton icon={CopyIcon} accessibilityLabel="Copy refund id" onClick={onClick} />
  </CopyIconWrapper>
);

export const CopyWrapper = ({
  children,
  onClick,
}: {
  children: React.ReactNode;
  onClick: () => void;
}): JSX.Element => (
  <Box display="inline-flex" alignItems="center" gap="spacing.2">
    {children}
    <CustomClipboard>
      <CopyButton onClick={onClick} />
    </CustomClipboard>
  </Box>
);

export const ErrorWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  gap: ${theme.spacing[3]}px;
  height: 90vh;
  width: 275px;
  margin: 0 auto;
  text-align: center;
`,
);

export const NotesKey = styled.span(
  ({ theme }: { theme: Theme }) => `
  font-weight: ${theme.typography.fonts.weight.bold};
`,
);

export const TooltipWrapper = styled.span`
  & > div {
    vertical-align: middle;
    cursor: pointer;
  }
`;

export const StyledChevron = styled.span`
  position: absolute;
  top: ${({ theme }: { theme: Theme }) => `${theme.spacing[4]}px`};
`;

export const StyledAmountContainer = styled.div`
  position: absolute;
  right: 0;
  top: ${({ theme }: { theme: Theme }) => `${theme.spacing[3]}px`};
`;

export const StyledGoBackBtn = styled.div(
  ({ theme }: { theme: Theme }) => `
  position:relative;
  @media screen and (max-width: ${theme.breakpoints.m}px) {
    margin-top: ${({ theme }: { theme: Theme }) => `${theme.spacing[9]}px`};
  };
`,
);

export const StyledAmountWrapper = styled.div<{ type: string; fontSize: string }>`
  .rzp-amount {
    .rzp-currency,
    .rzp-whole,
    .rzp-paise {
      color: ${({ type, theme }: { type: string; theme: Theme }) => getAmountColor(type, theme)};
      font-weight: 600;
      font-size: ${({ fontSize }: { fontSize: string }) => `${fontSize}px`};
    }
  }
`;

export const StyledBankTransferCollapser = styled.div`
  display: flex;
  gap: 10px;
  justify-content: flex-start;
  align-items: center;
  cursor: pointer;
`;
