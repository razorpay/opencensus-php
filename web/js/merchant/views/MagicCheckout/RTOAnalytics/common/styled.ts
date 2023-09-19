import styled from 'styled-components';

export const PrepayInsightsHeader = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  padding: 8px 12px;
  background: #fff;
  border: 1px solid #e0e8f4;
  border-radius: 2px;
  top: 68px;
  box-shadow: 0 0 0 #3f3f44, 0px 1px 3px #3f3f4426;

  .cumulative {
    width: 100%;
    display: flex;
    align-items: center;
    text-align: center;
    justify-content: space-around;

    .content-container {
      display: flex;
      flex-direction: column;
      padding: 2px;
    }

    .content-container: last-child {
      border-right: none;
    }

    .widget-header {
      font-weight: 700;
      font-size: 16px;
    }

    .total-color {
      border: 2px solid #6886b7;
      border-radius: 1.5px;
      background-color: #6886b7;
      margin-right: 8px;
    }

    .medium-color {
      border: 2px solid #ec9a37;
      border-radius: 1.5px;
      background-color: #ec9a37;
      margin-right: 8px;
    }

    .safe-color {
      border: 2px solid #7eb471;
      border-radius: 1.5px;
      background-color: #7eb471;
      margin-right: 8px;
    }

    .number {
      font-size: 18px;
      font-weight: 700;
      display: flex;
      justify-content: center;
    }
  }
`;

export const Partition = styled.div`
  border-right: 1px solid #99b3d980;
  height: 68px;
`;
