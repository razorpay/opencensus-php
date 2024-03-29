import styled from 'styled-components';

export const MetricsDashboard = styled.div`
  display: block;
`;

export const TopBar = styled.div`
  display: flex;
  flex-direction: column;
  background-color: #ececec;
  padding: 20px 24px;
  .panel-action-item {
    .btn-default[disabled] {
      position: relative;
      color: #58666e73;
      border-color: #cfdadd73;
      opacity: 1;
      &:hover {
        .rzp-tooltip {
          position: absolute;
          top: 0;
          right: 0;
          margin-top: -35px;
          white-space: nowrap;
          opacity: 1;
        }
      }
    }
    .btn-default {
      width: 78px;
      padding: 9px 10px;
      background-color: #fff;
      z-index: 0;
    }
    .active {
      background-color: #f3f8fe;
      box-shadow: none;
      border-color: #336cd5;
      z-index: 1;
    }
  }
  @media (min-width: 768px) {
    justify-content: space-between;
    align-items: center;
    flex-direction: row;
  }
`;

export const FilterParent = styled.div`
  padding-bottom: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

export const DatePickerGroup = styled.div`
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-left: 0;
  margin-bottom: 'calc(%s / 2)' % 8px;
  align-items: center;
  .bold {
    font-weight: 600;
  }
`;
export const DateRangeContainer = styled.div`
  height: auto;
`;

export const DateTimePickerDiv = styled.div`
  display: inline-flex;
  align-items: center;
  border: none;
  flex-wrap: wrap;
`;

export const DateTimeContainer = styled.div`
  > div {
    > input.form-control {
      width: 145px;
      height: 28px;
      font-weight: 600;
      padding: 6px;
      text-align: center;
      border: none;
    }

    > input.form-control[readonly] {
      background-color: #fff;
    }

    .rdtPicker {
      th.rdtNext span,
      th.rdtPrev span {
        border: none;
      }

      th.rdtSwitch {
        pointer-events: none;
        cursor: default;
      }

      .rdtTime th.rdtSwitch {
        pointer-events: auto;
        cursor: pointer;
      }
    }
  }
`;

export const DateTimePickerSeperator = styled.span`
  font-size: 12px;
  padding: 10px 0;
`;

export const MetricsPanelContainer = styled.div`
  .metrics-panel {
    background: #f5f5f5;
    margin-bottom: 10px;
  }
  hr {
    border-top-color: #adaeaf;
    margin: 0;
  }
  .metrics-body {
    border: 1px solid #e8eaeb !important;
    margin-bottom: 14px;
    padding: 24px 20px 28px !important;
    display: flex;
    height: inherit !important;
  }
`;

export const CardContainer = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-gap: 20px;
  padding: 20px 24px;
  @media (max-width: 767px) {
    display: flex;
    flex-direction: column;
  }
`;
export const Card = styled.div`
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  padding-top: 16px;
`;

export const CardHeader = styled.div`
  padding: 0 24px;
  > :first-child {
    font-weight: 600;
    font-size: 18px;
    color: #000;
  }
  > :second-child {
    color: #808080;
    font-size: 14px;
  }
`;

export const CardCenter = styled.div`
  font-size: 56px;
  font-weight: 600;
  margin: 10px 0;
  color: #000;
  text-align: center;
  .alert {
    font-size: 12px;
  }
`;

export const CardFooter = styled.div`
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  flex-direction: row;
  padding: 0 24px;
  > :first-child {
    border-right: 1px solid rgba(0, 0, 0, 0.05);
  }
  > div {
    width: 50%;
    text-align: center;
    padding: 16px 0px;
    font-size: 14px;
    color: #858585;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: row;
    cursor: pointer;
    > * {
      padding-right: 5px;
    }
    .green {
      color: #00a040;
      flex-shrink: 0;
    }
    .red {
      color: rgb(191, 122, 3);
      flex-shrink: 0;
    }
  }
`;

export const CenterContainer = styled.div`
  display: flex;
  align-items: center;
  justify-content: center;
  height: 300px;
  padding: 20px 24px;
`;

export const LoadingErrorContainer = styled.div`
  display: flex;
  flex-direction: column;
  > :first-child {
    display: flex;
    align-items: center;
    justify-content: center;
    svg {
      width: 21px;
      height: 18px;
      > path {
        fill: #ffa665;
      }
    }
  }
`;
export const LoadingErrorContainerTitle = styled.span`
  margin-left: 6px;
  font-size: 18px;
`;

export const LoadingErrorContainerDescription = styled.span`
  font-size: 14px;
  margin-top: 6px;
  text-align: center;
`;

export const ChartCardContainer = styled.div`
  display: flex;
  flex-direction: column;
  padding: 20px 24px;
  > div {
    margin-bottom: 30px;
  }
`;

export const GraphSection = styled.div`
  margin-top: 30px;
  width: 100%;
  padding: 0px 16px;
`;

export const LineChartArea = styled.div`
  height: 300px;
  margin-bottom: 5px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-left: none;
`;

export const GraphLabel = styled.div`
  display: flex;
  align-items: center;
  justify-content: center;
  padding-bottom: 16px;
  color: #5f5f5f;

  @media (max-width: 767px) {
    flex-wrap: wrap;
  }
`;

export const Dot = styled.span<{ backgroundColor?: string }>(
  ({ backgroundColor }) => `
  height: 10px;
  width: 10px;
  background-color: ${backgroundColor};
  border-radius: 50%;
  margin-right: 5px;
  margin-left: 20px;
`,
);
export const SelectedMetricsContainer = styled.div`
  background: #fff;
  padding: 20px;
  display: block;
  @media (max-width: 767px) {
    padding: 0;
  }
`;
export const BreadcrumbArea = styled.div`
  padding-left: 15px;
`;

export const MetricsTopBar = styled.div`
  display: flex;
  align-items: center;
  padding-bottom: 20px;

  .i-arrow-back {
    cursor: pointer;
    font-size: 18px;
    padding-top: 2px;
  }
`;
export const SelectedMetric = styled.span`
  font-weight: 600;
  color: #000;
`;
