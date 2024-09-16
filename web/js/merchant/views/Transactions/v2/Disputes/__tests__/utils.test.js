import qs from 'query-string';

import {
  disputeDurationOptions,
  disputeDurationSectionOptions,
  FILTER_TITLE,
  statusOptions,
  statusSectionOptions,
} from 'merchant/views/Transactions/v2/Disputes/constants';
import {
  getDefaultFilterSelectedOptions,
  getErrorMessage,
  getFilterValues,
  getKeyByValue,
  getOptions,
  getTags,
  getValidFilterKeys,
  isDisputesRevampV2Enabled,
} from 'merchant/views/Transactions/v2/Disputes/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

jest.mock('merchant_common/reducers/notifications', () => ({
  showNotification: jest.fn(),
}));

jest.mock('query-string', () => ({
  parse: jest.fn(() => ({})),
}));

const defaultSelectedFilters = { [FILTER_TITLE.PHASES_OF_DISPUTE]: ['Retrieval'] };

describe('DisputesV2 - utils', () => {
  it('should return false if Disputes_Revamp_V2 experiment is not enabled', () => {
    const splitz = { abExperiments: { Disputes_Revamp_V2: false } };
    const user = { isOrgRZP: true };
    expect(isDisputesRevampV2Enabled(splitz, user)).toBe(false);
  });

  it('should return false if user isOrgCurlec', () => {
    const splitz = { abExperiments: { Disputes_Revamp_V2: true } };
    const user = { isOrgCurlec: true };
    expect(isDisputesRevampV2Enabled(splitz, user)).toBe(false);
  });

  it('should return mobile options if isMobile is true', () => {
    const result = getOptions(true);
    expect(result).toEqual({
      disputeDurationOptions: disputeDurationSectionOptions,
      statusOptions: statusSectionOptions,
    });
  });

  it('should return desktop options if isMobile is false', () => {
    const result = getOptions(false);
    expect(result).toEqual({
      disputeDurationOptions,
      statusOptions,
    });
  });

  it('should return key by value', () => {
    const obj = { key1: 'value1', key2: 'value2' };
    const result = getKeyByValue(obj, 'value2');
    expect(result).toBe('key2');
  });

  it('should return undefined if value is not found', () => {
    const obj = { key1: 'value1', key2: 'value2' };
    const result = getKeyByValue(obj, 'value3');
    expect(result).toBe('');
  });

  it('should return filter values based on filter and value', () => {
    const filter = 'phase';
    const value = 'retrieval';

    const result = getFilterValues(filter, value);
    expect(result).toEqual({
      key: 'Phases of dispute',
      filterValues: ['Retrieval'],
    });
  });

  it('should return valid filter keys', () => {
    const result = getValidFilterKeys(defaultSelectedFilters);
    expect(result).toEqual({
      phase: 'retrieval',
    });
  });

  it('getDefaultFilterSelectedOptions', () => {
    qs.parse.mockReturnValue({ phase: 'retrieval' });
    expect(getDefaultFilterSelectedOptions()).toEqual(defaultSelectedFilters);
  });

  it('should return tags based on selected filters', () => {
    const expected = [
      {
        tagTitle: FILTER_TITLE.PHASES_OF_DISPUTE,
        tagValue: 'Phase: Retrieval',
        filterValue: 'Retrieval',
      },
    ];

    expect(getTags(defaultSelectedFilters)).toEqual(expected);
  });

  it('should show an error notification', () => {
    getErrorMessage();
    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: 'Failed to download the file',
    });
  });
});
