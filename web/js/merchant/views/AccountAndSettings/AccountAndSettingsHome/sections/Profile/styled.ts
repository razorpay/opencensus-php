import styled, { css } from 'styled-components';

export const ProfileContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 12px;
`;

export const ProfileContent = styled.div`
  padding: 16px;
  box-shadow: 0px 1px 2px rgba(21, 45, 75, 0.2), 0px 0px 1px rgba(21, 45, 75, 0.2);
  border-radius: 4px;
  background: #ffffff;
  width: 752px;
  height: 158px;
  display: flex;
`;

export const UserProfile = styled.div`
  display: flex;
  gap: 16px;
  flex: 0 0 50%;
  border-right: 1px solid rgba(121, 135, 156, 0.09);
  padding-right: 23px;
`;

export const UserInfo = styled.div`
  padding-left: 23px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex: 0 0 50%;
  @media screen and (max-width: 768px) {
    padding: 24px 0;
  }
`;

export const Details = styled.div`
  flex-grow: 1;
`;

export const UserInfoItem = styled.div`
  display: flex;
  justify-content: space-between;
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

export const Subheading = styled.h6`
  font-size: 16px;
  font-weight: 400;
  color: #435775;
  margin-top: 4px;
`;

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

export const MerchantText = styled.div``;

export const MerchantCopy = styled.div`
  color: #2a86f3;
  display: flex;
  gap: 4px;
  align-items: center;
`;

export const IconText = styled.div`
  display: flex;
  gap: 8px;
  align-items: end;
`;

export const VerificationContainer = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  @media screen and (max-width: 768px) {
    margin: 26px 0;
  }
`;

export const TooltipContainer = styled.div`
  color: #324664;
  .rzp-tooltip {
    width: 242px !important;
    .rzp-tooltip-inner {
      padding: 10px;
      box-shadow: 0px 3px 8px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.1);
      border-radius: 4px;
    }
  }
`;

export const MobileProfileContainer = styled.div``;

export const MobileProfileView = styled.div<any>`
  display: flex;
  justify-content: space-between;
  padding: 24px 0;
  align-items: flex-start;
  ${({ isOpen }) =>
    isOpen &&
    css`
      padding: 24px 0 11px;
    `}
`;

export const ProfileDetail = styled.div`
  display: flex;
  gap: 16px;
`;
