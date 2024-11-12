import styled, { css } from 'styled-components';

export const CardComponent = styled.div`
  padding: 24px 0px;
  @media screen and (min-width: 768px) {
    width: 368px;
    height: auto;
    padding: 0 20px 20px;
    background: #ffffff;
    box-shadow: 0px 1px 2px rgba(21, 45, 75, 0.2), 0px 0px 1px rgba(21, 45, 75, 0.2);
    border-radius: 4px;
  }
`;

export const CardHeader = styled.div`
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 16px 0;
  @media screen and (max-width: 768px) {
    gap: 8px;
    padding: 0;
  }
`;

export const SubSectionItem = styled.div`
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-start;
`;

export const ProductIcon = styled.div<any>`
  width: 32px;
  height: 32px;
  border-radius: 50%;
  ${({ iconBackground }) =>
    iconBackground &&
    css`
      background: ${iconBackground};
    `}
  position: relative;
  i {
    color: #ffffff;
    position: absolute;
    left: 50%;
    top: 55%;
    transform: translate(-50%, -50%);
  }
  @media screen and (max-width: 768px) {
    width: 24px;
    height: 24px;
    i {
      font-size: 75%;
    }
  }
`;

export const CardItems = styled.div<any>`
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: ${({ isShimmer }) => (isShimmer ? 12 : 8)}px;
  @media screen and (max-width: 768px) {
    gap: 16px;
    padding-top: 24;
  }
`;
