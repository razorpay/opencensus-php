import moment from 'moment';
import {
  getPreAndPostMagicChartOptions,
  getPreMagicOrderData,
  getPostMagicOrderData,
} from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic/utils';

describe('getPreAndPostMagicChartOptions', () => {
  it('returns chart options with modified callback functions', () => {
    const breakdown = 'monthly';
    const options = getPreAndPostMagicChartOptions(breakdown);

    const labels = ['Previous RTO rate', 1685577600000, 1688169600000];
    // Verify that the xAxes ticks callback has been modified
    expect(options.scales.xAxes[0].ticks.callback('value', 0, labels)).toBe(labels[0]);
    expect(options.scales.xAxes[0].ticks.callback('value', 1, labels)).toBe('Jun');

    // Verify that the yAxes ticks callback has been modified
    expect(options.scales.yAxes[0].ticks.callback(10)).toBe('10%');

    // Verify that the tooltips label and footer callbacks have been modified
    const labelCallback = options.tooltips.callbacks.label;

    const tooltipItem = {
      index: 0,
      datasetIndex: 0,
      yLabel: 42,
    };

    const data = {
      datasets: {
        0: { label: 'Previous RTO rate' },
      },
    };

    expect(labelCallback(tooltipItem, data)).toBe('Previous RTO rate: 42%');

    expect(labelCallback({ ...tooltipItem, index: 1 }, data)).toBeNull();

    const footerCallback = options.tooltips.callbacks.footer;

    expect(footerCallback([{ xLabel: 'Previous RTO rate' }])).toBeNull();
    expect(footerCallback([{ xLabel: 1685577600000 }])).toBe('Jun 01 2023 - Jun 30 2023');

    // Verify that the tooltips label color callback have been modified
    const labelColorCallback = options.tooltips.callbacks.labelColor;
    const chart = {
      config: {
        data: {
          datasets: {
            0: {
              borderColor: '#12345',
            },
          },
        },
      },
    };

    expect(labelColorCallback({ datasetIndex: 0 }, chart)).toEqual({
      backgroundColor: '#12345',
      borderColor: 'transparent',
    });
  });
});

describe('getPreMagicOrderData', () => {
  it('returns gradient color when chartArea exists', () => {
    const mockContext = {
      chart: {
        ctx: {
          createLinearGradient: jest.fn(() => ({
            addColorStop: jest.fn(),
          })),
        },
        chartArea: {
          top: 0,
          bottom: 100,
        },
      },
    };

    const backgroundColor = getPreMagicOrderData(
      { premagic_rto_rate: { rto_rate: 42 } }, // Mock data
    ).backgroundColor(mockContext);

    // Expect that createLinearGradient has been called
    expect(mockContext.chart.ctx.createLinearGradient).toHaveBeenCalledWith(0, 100, 0, 0);

    // Expect that backgroundColor is not null
    expect(backgroundColor).not.toBeNull();
  });

  it('returns null when chartArea does not exist', () => {
    const mockContext = {
      chart: {
        ctx: {
          createLinearGradient: jest.fn(() => ({
            addColorStop: jest.fn(),
          })),
        },
        chartArea: null,
      },
    };

    const backgroundColor = getPreMagicOrderData({
      premagic_rto_rate: { rto_rate: 42 },
    }).backgroundColor(mockContext);

    // Expect that createLinearGradient has not been called
    expect(mockContext.chart.ctx.createLinearGradient).not.toHaveBeenCalled();

    // Expect that backgroundColor is null
    expect(backgroundColor).toBeNull();
  });
});

describe('getPostMagicOrderData', () => {
  it('returns gradient color when chartArea exists', () => {
    const mockContext = {
      chart: {
        ctx: {
          createLinearGradient: jest.fn(() => ({
            addColorStop: jest.fn(),
          })),
        },
        chartArea: {
          top: 0,
          bottom: 100,
        },
      },
    };

    const rawData = {
      postmagic_rto_rate: [
        {
          total_rto_rate: 42,
          period: 1693547101,
        },
      ],
    };
    const breakdown = 'weekly';
    const startMoment = moment();
    const labels = ['1685577600000', '1688169600000'];

    const backgroundColor = getPostMagicOrderData(
      rawData,
      breakdown,
      startMoment,
      labels,
    ).backgroundColor(mockContext);

    // Expect that createLinearGradient has been called with correct arguments
    expect(mockContext.chart.ctx.createLinearGradient).toHaveBeenCalledWith(0, 100, 0, 0);

    // Expect that backgroundColor is not null
    expect(backgroundColor).not.toBeNull();
  });

  it('returns null when chartArea does not exist', () => {
    const mockContext = {
      chart: {
        ctx: {
          createLinearGradient: jest.fn(),
        },
        chartArea: null,
      },
    };

    const rawData = {
      postmagic_rto_rate: [
        {
          total_rto_rate: 42,
          period: 1693547101,
        },
      ],
    };
    const breakdown = 'weekly';
    const startMoment = moment();
    const labels = ['1685577600000', '1688169600000'];

    const backgroundColor = getPostMagicOrderData(
      rawData,
      breakdown,
      startMoment,
      labels,
    ).backgroundColor(mockContext);

    // Expect that createLinearGradient has not been called
    expect(mockContext.chart.ctx.createLinearGradient).not.toHaveBeenCalled();

    // Expect that backgroundColor is null
    expect(backgroundColor).toBeNull();
  });
});
