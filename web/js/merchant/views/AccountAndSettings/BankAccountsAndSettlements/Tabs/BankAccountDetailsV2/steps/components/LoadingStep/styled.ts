import styled from 'styled-components';

export const StyledLoadingContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 16px;
  align-items: center;
  width: 468px;
  @media screen and (max-width: 768px) {
    width: 100%;
    gap: 20px;
    padding: 24px 0 44px;
  }
`;

export const DescriptionText = styled.div`
  width: 360px;
  text-align: center;
  @media screen and (max-width: 768px) {
    width: 80%;
  }
`;

export const StyledTitleGroup = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  text-align: center;
`;
