import styled from 'styled-components';

export const PageLayoutContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 32px;
  padding: 32px 0 32px 32px;
  @media screen and (max-width: 768px) {
    padding: 10px 16px;
    gap: 0px;
    background: #ffffff;
  }
`;
