import { Alert } from '@razorpay/blade/components';
import styled, { css } from 'styled-components';

export const StyledStepContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 24px;
`;

export const StyledUploadContainer = styled.div`
  padding: 16px;
  background: #f2f4f8;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  @media screen and (max-width: 768px) {
    padding: 0px;
  }
`;

export const UploadSectionTab = styled.div`
  width: 100%;
`;

export const StyledSubHeader = styled.div`
  display: flex;
  flex-direction: column;
  gap: 12px;
`;

export const TabsHeader = styled.div`
  display: flex;
`;

export const TabsHeaderItem = styled.div`
  flex: 1;
  padding: 16px;
  border: 1px solid rgba(121, 135, 156, 0.18);
  border-radius: 4px 4px 0px 0px;
  cursor: pointer;
  background: #f8f9fb;
  ${({ isActive }) =>
    isActive
      ? css`
          background: #ffffff;
          border-radius: 2px 2px 0px 0px;
          padding: 12px 16px 16px;
          border: none;
          border-top: 4px solid #2a86f3;
        `
      : css`
          h6 {
            font-weight: 400;
            color: #5d6d86;
          }
        `}
  @media screen and (max-width: 768px) {
    padding: 12px 15px 15px;
    border: none;
    border-top: 4px solid rgba(121, 135, 156, 0.09);
    border-radius: 2px 2px 0px 0px;
    ${({ isActive }) =>
      isActive
        ? css`
            background: #ffffff;
            border-top: 4px solid #2a86f3;
          `
        : css`
            h6 {
              font-weight: 400;
              color: #5d6d86;
            }
          `}
  }
`;

export const TabContent = styled.div`
  background: #ffffff;
  width: 100%;
  padding: 24px;
  border-radius: 0px 0px 4px 4px;
  @media screen and (max-width: 768px) {
    padding: 24px 12px;
  }
`;

export const StyledAlert = styled(Alert)`
  max-width: 100%;
`;
