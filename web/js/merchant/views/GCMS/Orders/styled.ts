import styled from 'styled-components';

export const OrderFooterWrapper = styled.div`
  position: fixed;
  background-color: white;
  bottom: 0;
  right: 0;
  left: 248px;
  z-index: 999999;

  @media (max-width: 1200px) {
    max-width: 1160px;
  }
  @media (max-width: 767px) {
    width: 100%;
    left: 0;
  }
`;

export const OrderCardItemContainer = styled.div`
  display: flex;
  flex-direction: column;
  justify-content: center;
  background-color: #f5f8fe;
  border-radius: 5px;
  padding: 8px 8px 8px 8px;
  margin: 8px 12px 8px 12px;
`;
