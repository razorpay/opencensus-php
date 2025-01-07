import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const CarouselContainer = styled.div`
  background-color: #000223;
`;

export const PaymentsRecapContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  background-color: #000223;
  padding: ${theme.spacing[5]}px ${theme.spacing[7]}px;
  padding-top: ${theme.spacing[10]}px;
  border-radius: ${theme.spacing[3]}px;
`,
);

export const StyledTextHighlight = styled.span<{ fontSize?: string }>(
  ({ theme, fontSize }) => `
    font-weight: ${theme.typography.fonts.weight.semibold};
    font-style: italic;
    color: ${theme.colors.primary};
    font-size: ${fontSize || `${theme.typography.fonts.size[200]}px`};
    line-height: ${fontSize || `${theme.typography.fonts.size[200]}px`};
  `,
);

export const StyledTextHeading = styled.p<{ fontSize?: string; color?: string }>(
  ({ theme, fontSize, color }) => `
  font-weight: ${theme.typography.fonts.weight.semibold};
  font-style: italic;
  color: ${color || '#c1ff84'};
  font-size: ${fontSize || `${theme.typography.fonts.size[200]}px`};
  line-height: ${fontSize || `${theme.typography.fonts.size[200]}px`};
  text-align: center;
  `,
);

export const SocialShareBottomSheet = styled.div(
  (_) => `
  [data-blade-component='bottom-sheet'] {
    background-color: rgb(28,40,56);
  }
  [data-testid='bottomsheet-backdrop']{
    background-color: transparent;
  }
`,
);

export const StyledIcon = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: grid;
  place-items: center;
  background-color: #FFFFFF;
  height: ${theme.spacing[10]}px;
  width: ${theme.spacing[10]}px;
  border-radius: ${theme.spacing[2]}px;
  cursor: pointer;
`,
);

export const BannerBtn = styled.button<{ isMobileBanner: boolean }>(
  ({ theme }) => `
  font-size: ${theme.typography.fonts.size[100]}px;
  min-height: 36px;
  padding: ${theme.spacing[0]}px ${theme.spacing[5]}px;
  width: max-content;
  cursor: pointer;
  background-color: #c1ff84;
  color: #2d2a26;
  font-weight: ${theme.typography.fonts.weight.medium};
  border-radius: 6px;
  font-style: italic;
  border-width: ${theme.spacing[0]}px;
  `,
);

export const TextWrapper = styled.p<{
  isMobileBanner?: boolean;
  top?: string;
  left?: string;
  right?: string;
  fontSize?: string;
  fontWeight?: string;
}>(({ theme, top, left, right, fontSize, fontWeight }) => ({
  alignItems: 'center',
  fontStyle: 'italic',
  fontSize: fontSize || '80px',
  fontWeight: fontWeight || 800,
  color: '#c1ff84',
  position: 'absolute',
  zIndex: 1,
  left: left || '80px',
  top: top || '170px',
  right: right || `${theme.spacing[0]}px`,
}));

export const BestTimeContainer = styled.p<{
  fontSize?: string;
}>(
  ({ theme, fontSize }) => `
  font-size: ${fontSize || '10px'};
  font-weight: ${theme.typography.fonts.weight.regular};
  color: #b4cdfd;
  display: flex;
  flex-direction: row;
  align-items: center;
  `,
);

export const BestTimePercentage = styled.div<{
  isMobileBanner?: boolean;
  width?: string;
  isBestTime: boolean;
  height?: string;
}>(
  ({ theme, isBestTime, width, height }) => `
  background-color: ${isBestTime ? '#B4CDFD' : 'transparent'};
  height: ${height || '10px'};
  border-radius: 14px;
  width: ${width || `${theme.spacing[0]}px`};
  margin-right: 5px;
  border: ${isBestTime ? `${theme.spacing[0]}px` : '1px solid #B4CDFD'};
  `,
);
