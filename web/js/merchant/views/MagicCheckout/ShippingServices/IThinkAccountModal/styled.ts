import styled from 'styled-components';

export const IntegrationModalContainer = styled.div`
  display: flex;
`;
export const DemoVideoContainer = styled.div`
  background-color: #0e1938;
  background-size: cover;
  padding: 36px 48px 0 48px;
  width: 50%;
`;
export const FormContainer = styled.div`
  padding: 0;
  background-color: #fff;
  width: 50%;
`;

export const IntegrationModalIcon = styled.img`
  position: absolute;
  top: 0;
  left: calc(100% - 52%);
  margin-top: 34px;
  z-index: 1;
  width: 40px;
  height: 40px;
  background: #fff;
  padding: 4px;
  filter: drop-shadow(0px 0px 4px rgba(0, 0, 0, 0.15));
`;

export const FormCtaContainer = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;

  .secondary-cta {
    margin-left: 12px;
    color: #262d3a;
  }

  .primary-cta {
    border-radius: 2px;
    background: #1583f1;
    color: #fff;
    font-weight: 700;
    padding: 10px 12px;
  }
`;

export const IThinkLogisticsInfo = styled.div`
  padding: 12px;
  background: #f8f9ff;
  display: flex;

  .ithink-connect-info-icon {
    border-radius: 50%;
    background: #b8c0e6;
    height: 15px;
    width: 30px;
    margin-right: 8px;
    margin-top: 4px;
    text-align: center;
    font-size: 12px;
    color: #fff;
  }
`;

export const StyledVideo = styled.video`
  height: 430px;
`;

export const VideoInfoText = styled.div`
  display: flex;
  align-items: center;
  gap: 10px;
  color: #9fa3af;
  margin: 14px 0;

  .i-info-circle {
    position: relative;
    top: 2px;
  }
`;

export const PointsContainer = styled.div`
  display: flex;
  margin-top: 24px;

  .pointer-stroke {
    border-left: 3px solid #d5d7e2;
  }

  .pointer-container {
    top: -2px;
    left: -9px;
  }
`;
export const PointerHeading = styled.p`
  color: #262d3a;
  font-weight: 700;
  line-height: 8px;
`;
export const PointListContainer = styled.div`
  margin-top: 8px;
`;
export const List = styled.ul`
  padding-left: 12px;
  margin: 0;
`;
export const ListItem = styled.li`
  margin-bottom: 10px;

  &:last-child {
    margin-bottom: 0;
  }
`;

export const HighlightPoint = styled.div`
  width: 8%;
`;
export const HighlightStroke = styled.div`
  width: 92%;
`;

export const FormWrapper = styled.div`
  margin-top: 28px;
  display: flex;
  flex-direction: column;
  gap: 30px;
`;
export const FieldLabel = styled.label`
  color: #262d3a;
  font-weight: 700;

  sup {
    color: red;
    font-size: 14px;
    position: relative;
    top: -2px;
  }
`;

export const FieldContainer = styled.div`
  .Input {
    margin: 0;
  }
`;

export const LinkAccountWrapper = styled.div`
  height: 100%;

  .link-account-desc {
    margin-bottom: 48px;
  }
`;

export const PointsWrapper = styled.div`
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 300px;
`;
