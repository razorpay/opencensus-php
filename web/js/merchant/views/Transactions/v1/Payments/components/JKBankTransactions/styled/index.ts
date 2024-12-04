import styled from 'styled-components';

export const DropdownWrapper = styled.div`
  & button {
    margin: 0;
    padding: 0;
    padding: 12px 6px;
    margin-bottom: 12px;
  }
`;

export const StyledContainer = styled.div`
  display: flex;
  width: 100%;
  justify-content: space-between;
  padding: 12px 18px;
  margin-bottom: 10px;
  border-bottom: 1px solid rgba(22, 47, 86, 0.05);

  span.rzp-paise {
    font-size: 12px;
    color: rgba(22, 47, 86, 0.38);
  }

  span.rzp-whole {
    font-size: 16px;
    color: rgba(22, 47, 86, 0.87);
  }

  span.rzp-currency {
    font-size: 12px;
    color: rgba(22, 47, 86, 0.38);
  }
`;
