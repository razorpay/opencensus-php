import styled from 'styled-components';

export const UserInfoContainer = styled.div`
  padding-left: 23px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex: 0 0 50%;
  @media screen and (max-width: 768px) {
    padding: 24px 0;
  }
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

export const IconText = styled.div`
  display: flex;
  gap: 8px;
  align-items: end;
`;

export const TooltipContainer = styled.div`
  color: #324664;
  .rzp-tooltip {
    width: 242px !important;
    .rzp-tooltip-inner {
      padding: 10px;
      box-shadow: 0px 3px 8px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.1);
      border-radius: 4px;
      .rzp-popover-content {
        .rzp-popover-body {
          div {
            white-space: pre-line;
          }
        }
      }
    }
  }
`;
