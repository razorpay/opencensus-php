import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

const StyledRadioBox = styled.div(
  ({
    theme,
    isRadioClick,
    paymentType,
  }: {
    theme: Theme;
    isRadioClick: 'INTERNAL' | 'PG';
    paymentType: 'INTERNAL' | 'PG';
  }) => `
        padding: ${theme.spacing[6]}px;
        border-radius: ${theme.border.radius.large}px;
        margin-bottom: ${theme.spacing[7]}px;
        border-bottom: ${theme.border.width.thick}px;
        border-style: solid;
        border-color:${
          isRadioClick === paymentType
            ? theme.colors.brand.primary[500]
            : theme.colors.surface.border.normal.lowContrast
        };
        &:hover {
          cursor: pointer;
        }
`,
);
const StyledHover = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: center;
  align-items: center;
  div {
    width: 20px;
  }
  p {
    margin-left:${theme.spacing[4]}px;
  }
`,
);
const StyleModalParent = styled.div`
  & > h1 {
    color: #9586f2;
  }
`;
const StyleImageBox = styled.div`
  width: 650px;
  & img {
    width: 100%;
    height: 100%;
  }
`;

export { StyledHover, StyledRadioBox, StyleModalParent, StyleImageBox };
