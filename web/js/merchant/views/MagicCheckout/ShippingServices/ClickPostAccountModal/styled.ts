import styled from 'styled-components';

export const ClickpostModalContainer = styled.div`
  max-width: 770px;
  display: flex;
  flex-direction: row;
`;

export const ClickpostInfoContainer = styled.div`
  width: 50%;
  padding: 32px;
  background-color: #0e1938;
`;

export const ClickpostFormContainer = styled.div`
  width: 50%;
`;

export const ClickpostModalIcon = styled.img`
  background: #fff;
  position: absolute;
  top: 0;
  left: calc(100% - 53%);
  top: 25px;
  z-index: 1;
  width: 50px;
  height: 50px;
  object-fit: contain;
  border-radius: 2px;
  padding: 4px;
  filter: drop-shadow(0px 0px 4px rgba(0, 0, 0, 0.15));
`;

export const ClickpostInfoHeader = styled.div`
  font-size: 14px;
  font-weight: 600;
  padding-bottom: 31px;
  color: #fff;
`;
export const HighlightPoint = styled.div`
  width: 8%;
`;

export const HighlightStroke = styled.div`
  width: 92%;
`;

export const PointerHeading = styled.p`
  color: #ffffff;
  font-weight: 600;
  word-break: break-word;
`;
export const PointerDescription = styled.p`
  color: #ffffff;
  word-break: break-word;
`;
export const PointsContainer = styled.div`
  display: flex;
  line-height: 24px;

  .pointer-stroke {
    border-left: 3px solid #d5d7e2;
  }

  .pointer-container {
    top: -2px;
    left: -9px;
  }
`;

export const FormWrapper = styled.div`
  margin-top: 28px;
  display: flex;
  flex-direction: column;
  gap: 30px;
`;

export const FormCtaContainer = styled.div`
  display: flex;
  align-items: center;
  justify-content: end;
  margin-bottom: 48px;

  .primary-cta {
    border-radius: 2px;
    background: #1583f1;
    color: #fff;
    font-weight: 600;
    padding: 10px 12px;
  }
`;

export const LinkAccountContent = styled.div`
  padding: 0 48px;
`;
