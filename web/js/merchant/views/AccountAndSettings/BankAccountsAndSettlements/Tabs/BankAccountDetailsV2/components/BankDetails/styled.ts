import styled, { css } from 'styled-components';

const handleChipColor = (type) => {
  switch (type) {
    case 'ON HOLD':
      return '#D13821';
    case 'ACTIVE':
    default:
      return '#008659';
  }
};

export const StyledBankDetailsContainer = styled.div`
  padding: 24px;
  background: #ffffff;
  border: 1px solid rgba(121, 135, 156, 0.18);
  border-radius: 4px;
  display: flex;
  gap: 24px;
  align-items: center;
  flex-wrap: wrap;
  @media screen and (max-width: 768px) {
    padding: 16px;
  }
`;

export const BankAccountIconSection = styled.div`
  width: 48px;
  height: 48px;
  background: #f2f4f8;
  border: 1px solid #dfe3e9;
  border-radius: 4px;
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const AccountDetails = styled.div`
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
  @media screen and (max-width: 768px) {
    min-width: 45%;
    &:nth-child(2) {
      flex: 0 1 auto;
    }
  }
`;

export const Chip = styled.span<any>`
  border-radius: 100px;
  color: #ffffff;
  padding: 2px 8px;
  display: flex;
  align-items: center;
  width: fit-content;
  cursor: pointer;
  background: ${({ value }) => handleChipColor(value)};
  span {
    font-weight: 600;
    font-size: 12px;
  }
  .i {
    font-size: 80%;
    margin-right: 4px;
  }
`;

export const StyledLink = styled.div<any>`
  ${({ isDisable }) =>
    isDisable &&
    css`
      button {
        cursor: not-allowed;
      }
    `}
  @media screen and (max-width: 768px) {
    flex: 1;
  }
`;

export const ChipContainer = styled.div`
  width: fit-content;
  .rzp-tooltip {
    width: 230px !important;
    .rzp-tooltip-inner {
      padding: 10px;
      box-shadow: 0px 3px 8px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.1);
      border-radius: 4px;
    }
  }
`;
