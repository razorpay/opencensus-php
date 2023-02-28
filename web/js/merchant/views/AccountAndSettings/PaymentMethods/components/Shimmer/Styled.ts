import { Theme } from '@razorpay/blade/components';
import { SectionContent } from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section/Styled';
import styled from 'styled-components';

export const SectionContentShimmer = styled(SectionContent)(
  ({ theme }: { theme: Theme }) => `
  width: 450px !important;
  height: 350px !important;
  padding: ${theme.spacing[7]}px;
`,
);

export const InnerSectionBox = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  flex-direction: column;
  row-gap: ${theme.spacing[4]}px;
`,
);
