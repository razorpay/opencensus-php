import {
  updateConfigData,
  updateConfigResult,
  createReportConfig,
  createReportResult,
  createReportConfigTwoProcess,
  createReportConfigTwoProcessResult,
} from 'merchant/views/Reconciliations/__test__/mock/fixture';
import {
  makeUpdatedReportConfigPayload,
  makeReportConfigPayload,
} from 'merchant/views/Reconciliations/helper';

describe('makeUpdatedReportConfigPayload', () => {
  it('should create an updated report config with the provided sample data', () => {
    const result = makeUpdatedReportConfigPayload(updateConfigData);

    expect(result).toEqual(updateConfigResult);
  });
});

describe('makeReportConfigPayload', () => {
  it('should create a report config payload with the provided sample data', () => {
    const result = makeReportConfigPayload(createReportConfig);

    expect(result).toEqual(createReportResult);
  });

  it('should handle empty joining config when less than 2 processes', () => {
    const result = makeReportConfigPayload(createReportConfigTwoProcess);

    expect(result).toEqual(createReportConfigTwoProcessResult);
  });
});
