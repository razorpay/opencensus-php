import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const UploadFileContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
        border: ${theme.border.width.thin}px dashed ${theme.colors.surface.border.gray.subtle};
        border-radius: ${theme.border.radius.medium}px;
        padding: ${theme.spacing[3]}px;
        height: 40px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: ${theme.spacing[3]}px;
    `,
);

export const UploadFileLabel = styled.label`
  width: 100%;
`;
