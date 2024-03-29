import styled from 'styled-components';

export const Container = styled.div`
  .title {
    padding: 0 0 12px 0;
    text-align: left;
    border-bottom: 1px solid rgba(22, 47, 86, 0.1);
    color: rgba(22, 47, 86, 0.87);
    font-weight: 800;
    font-size: 20px;
    line-height: 24px;
    margin: 0 32px;
  }

  .inner {
    padding: 32px;
    .description {
      margin-top: 12px;
      font-weight: normal;
      font-size: 13px;
      line-height: 19px;
      color: rgba(22, 47, 86, 0.87);
    }
    .text {
      font-size: 14px;
      line-height: 20px;
      color: rgba(22, 47, 86, 0.87);
    }
    .tips-container {
      margin-top: 12px;
      .wrapper {
        margin-top: 10px;
      }
      .flex {
        display: flex;
        flex-direction: row;
        align-items: center;
      }
      i {
        font-size: 14px;
        color: #239651;
      }
      .tip {
        margin: 0 0 0 10px;
      }
    }
    .action-point {
      margin-top: 32px;
      padding-top: 20px;
      border-top: 1px solid rgba(22, 47, 86, 0.12);
      color: rgba(22, 47, 86, 0.74);
    }
    a {
      text-decoration: none;
      color: white;
      background-color: none;
      .cta {
        color: white;
        margin-top: 24px;
        font-weight: 600;
        padding: 8px 29px 8px 24px;
      }
      img {
        margin: -2.5px 0 0 8px;
      }
    }
  }
`;
