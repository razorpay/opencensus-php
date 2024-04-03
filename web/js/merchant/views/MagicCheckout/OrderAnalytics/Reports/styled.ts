import styled from 'styled-components';

export const OrderAnalyticsReportsWrapper = styled.div`
  .spinner-container {
    display: flex;
    margin-top: 12%;
    justify-content: center;
    align-items: center;
    gap: 8px;
    cursor: not-allowed;
  }
  .download-icon {
    margin-top: 1%;
  }
`;

export const ReportsCardWrapper = styled.div`
  background: #fff;
  padding: 16px;
  border: 1px solid #e0e8f4;
  border-radius: 2px;
  margin: 8px 0;
  cursor: pointer;
  &:hover {
    border: 1px solid #528ff0;
    color: #528ff0;
  }

  .card {
    display: flex;
    justify-content: space-between;
  }
  .heading {
    font-size: 16px;
    font-weight: 700;
  }
`;
