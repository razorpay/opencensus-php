import styled from 'styled-components';

export const SummaryWidgetWrapper = styled.div`
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  @media screen and (max-width 1125px) {
    grid-template-columns: repeat(3, 1fr);
  }
  grid-gap: 10px;
`;

export const SummaryWidgetItem = styled.div`
  grid-column: span 1;
  display: flex;
  .vertical-partition {
    margin-left: 30px;
    opacity: 0.5;
  }
  .arrow {
    border: solid #99b3d9;
    border-width: 0 1px 1px 0;
    display: block;
    padding: 3px;
  }
  .right {
    position: relative;
    left: -3px;
    transform: rotate(-45deg);
  }
  .vup,
  .vdown {
    border: solid #99b3d9;
    border-width: 0 0 0 1px;
    display: block;
    height: 25px;
  }
  @media screen and (max-width 1125px) {
    grid-column: span 2;
  }
`;

export const SummaryWidget = styled.div(
  ({ color }) => `
  .accent {
    border: 2px solid ${color};
    border-radius: 1.5px;
    background-color: ${color};
  }
  .title {
    padding-left: 8px;
  }
  .value {
    font-size: 18px;
    font-weight: 700;
    display: flex;
    justify-content: flex-end;
  }
`,
);
