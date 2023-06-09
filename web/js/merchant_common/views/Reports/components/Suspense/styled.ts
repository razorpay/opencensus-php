import styled from 'styled-components';
import { flexCentered } from 'merchant_common/views/Reports/components/styled';

export const SpinnerContainer = styled.div<{
  minWidth?: number;
}>(
  ({ minWidth }) => `
  width: 100%;
  height: calc(100vh - 200px);
  ${flexCentered}
  ${minWidth && `min-width: ${minWidth}px;`}
`,
);
