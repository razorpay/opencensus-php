import styled from 'styled-components';

export const CardContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 12px;
  @media screen and (max-width: 768px) {
    gap: 0;
  }
`;

export const CardContent = styled.div`
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  @media screen and (max-width: 768px) {
    flex-direction: column;
    gap: 0;
  }
`;
