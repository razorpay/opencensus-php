import styled from 'styled-components';

export const StyledMerchantDetails = styled.div`
  display: flex;
  justify-content: space-between;
  &:last-child {
    color: #2a86f3;
  }
  @media screen and (min-width: 768px) {
    margin-top: 8px;
    &:after {
      content: ' ',
      margin-top: 12px;
      border: 1px solid rgba(121, 135, 156, 0.09);
    }
  }
  @media screen and (max-width: 768px) {
    margin-bottom: 24px;
    padding: 5px 5px 0 0;
  }
`;

export const Pointer = styled.i`
  color: #2a86f3;
  cursor: pointer;
`;

export const SubInfo = styled.div`
  display: flex;
  align-items: center;
  gap: 8px;
`;
