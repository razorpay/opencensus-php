import styled from 'styled-components';

export const IntegrationContainer = styled.div`
  padding: 24px;
  background-color: #fff;
`;

export const ContentWrapper = styled.div`
  display: flex;
  justify-content: space-between;
`;

export const InfoLink = styled.a`
  color: #528ff0;
  font-weight: 700;
  text-decoration: underline;
`;

export const FormCtaContainer = styled.div`
  display: flex;
  align-items: center;
  margin: 28px 0;
  justify-content: space-between;

  .secondary-cta {
    margin-left: 12px;
    color: #262d3a;
  }

  .secondary-cta.back {
    margin-left: 82px;
  }

  .primary-cta {
    border-radius: 2px;
    background: #1583f1;
    color: #fff;
    font-weight: 700;
    padding: 10px 12px;
  }
`;
