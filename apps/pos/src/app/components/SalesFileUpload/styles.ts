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
    `,
);

export const UploadFileLabel = styled.label(
  ({ isDisabled }: { isDisabled?: boolean }) => `
    width: 100%;
    cursor: ${isDisabled ? 'not-allowed' : 'pointer'};
    opacity: ${isDisabled ? 0.5 : 1};
    `,
);
