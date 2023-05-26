import styled from 'styled-components';
import BaseAmount from 'common/ui/Amount';
import Shimmer from 'common/components/Shimmer';

export const Card = styled.div(
  ({ theme }) => `
background: ${theme.colors.surface.background.level2.lowContrast};
border: ${theme.border.width.thin}px solid ${theme.colors.brand.gray[400].lowContrast};
border-radius: ${theme.border.radius.small}px;
padding: ${theme.spacing[3]}px 80px ${theme.spacing[3]}px ${theme.spacing[4]}px;
width: 200px;
height: 80px;
`,
);

export const Amount = styled(BaseAmount)(
  ({ theme }) => `
white-space: nowrap;
font-size: ${theme.typography.fonts.size[400]}px;
line-height: ${theme.spacing[9]}px;
color: ${theme.colors.surface.text.normal.lowContrast};

.rzp-currency,
.rzp-paise {
  opacity: 1;
}

.rzp-whole {
  font-weight: ${theme.typography.fonts.weight.bold};
  opacity: 1;
}

.rzp-paise {
  font-size: ${theme.typography.fonts.size[200]};
  opacity: 1;
}`,
);

export const AmountShimmer = styled(Shimmer)(
  ({ theme }) => `
position: absolute;
margin-top: ${theme.spacing[3]}px`,
);
