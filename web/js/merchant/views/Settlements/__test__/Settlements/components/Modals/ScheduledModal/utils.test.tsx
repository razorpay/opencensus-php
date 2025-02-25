import {
  getAutomaticSettlementTime,
  getEsPartialAutomaticDateKey,
  getEsNudgeKey,
  getEsBannerKey,
  setEnableEsPartialAutomaticDate,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';
import * as LocalStorage from 'common/utils/localStorage';
import { NUDGE_TYPES } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';
import { NEW_BANNERS } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/banners/constants';

jest.mock('merchant/store', () => ({
  getState: () => ({
    session: {
      user: {
        id: '123123',
      },
    },
  }),
}));

jest.spyOn(LocalStorage, 'setItem');


test('test getAutomaticSettlementTime', () => {
  expect(
    getAutomaticSettlementTime() === '9 AM' || getAutomaticSettlementTime() === '5 PM',
  ).toBeTruthy();
});

test('test getEsPartialAutomaticDateKey', () => {
  expect(getEsPartialAutomaticDateKey()).toBe('ENABLE_ES_PARTIAL_AUTOMATIC_DATE-123123');
});

test('test getEsNudgeKey', () => {
  expect(getEsNudgeKey(NUDGE_TYPES.FULL_SUCCESS)).toBe(
    `SEEN_ES_NUDGE-${NUDGE_TYPES.FULL_SUCCESS}-123123`,
  );
});

test('test getEsBannerKey', () => {
  expect(getEsBannerKey(NEW_BANNERS.FULL_SHIFT_SUCCESS)).toBe(
    `SEEN_ES_BANNER-${NEW_BANNERS.FULL_SHIFT_SUCCESS}-123123`,
  );
});
test('test setEnableEsPartialAutomaticDate', () => {
  setEnableEsPartialAutomaticDate();
  expect(LocalStorage.setItem).toHaveBeenCalled();
});
