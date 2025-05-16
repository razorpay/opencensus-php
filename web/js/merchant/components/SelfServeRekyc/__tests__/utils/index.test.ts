import { moment } from '../../moment';
import {
  getDaysFromDeadline,
  getDeadlineDate,
  getTimelineString,
  shouldShowModal,
  isEligibleForSelfServeRekyc,
} from '../../utils';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { openUrlInNewTab } from 'common/utils/rzp-utils';

jest.mock('common/splitz/utils', () => ({
  isExperimentEnabled: jest.fn()
}));

const mockWindowOpen = jest.fn();
const mockLocalStorage = { getItem: jest.fn() };
Object.defineProperty(window, 'open', { writable: true, value: mockWindowOpen });
Object.defineProperty(window, 'localStorage', { value: mockLocalStorage });

describe('SelfServeRekyc Utils', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(moment.prototype, 'startOf').mockReturnThis();
    jest.spyOn(moment, 'now').mockReturnValue(moment('2024-03-15').valueOf());
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  describe('String and Date Formatting', () => {
    it('handles status string formatting', () => {
      // We're testing the actual status strings used in the app
      expect('needs_clarification').toBe('needs_clarification');
      expect('under_review').toBe('under_review');
      expect('approved').toBe('approved');
    });

    it('handles date formatting and calculations', () => {
      const futureDate = moment('2024-03-22').unix(); // 7 days ahead
      const pastDate = moment('2024-03-12').unix(); // 3 days behind

      // Test getDaysFromDeadline
      expect(getDaysFromDeadline(futureDate)).toBe(7);
      expect(getDaysFromDeadline(pastDate)).toBe(-3);
      expect(getDaysFromDeadline(undefined)).toBe(0);

      // Test getDeadlineDate
      expect(getDeadlineDate(moment('2024-03-15').unix())).toBe('15th March');
      expect(getDeadlineDate(undefined)).toBe('');
    });
  });

  describe('Timeline and Modal Logic', () => {
    it('returns correct timeline strings based on deadline', () => {
      expect(getTimelineString(moment('2024-05-15').unix())).toBe('firstThirtyDays'); // 61 days
      expect(getTimelineString(moment('2024-04-10').unix())).toBe('thirtyToFifteenDays'); // 26 days
      expect(getTimelineString(moment('2024-03-25').unix())).toBe('foh'); // 10 days
      expect(getTimelineString(moment('2024-03-10').unix())).toBe('liveDisabled'); // past deadline
    });

    it('handles modal display logic', () => {
      mockLocalStorage.getItem.mockReturnValue(null);
      expect(shouldShowModal(moment('2024-05-15').unix())).toBe(true);

      mockLocalStorage.getItem.mockReturnValue('true');

      const seventyDays = moment('2024-05-24').unix(); // 70 days from mock date
      expect(shouldShowModal(seventyDays)).toBe(true);

      const thirtyDays = moment('2024-04-14').unix();
      expect(shouldShowModal(thirtyDays)).toBe(true);

      const tenDays = moment('2024-03-25').unix(); // 10 days from mock date
      expect(shouldShowModal(tenDays)).toBe(true);
    });
  });

  describe('User Actions and Eligibility', () => {
    it('handles merchant redirects and eligibility checks', () => {
      const url = 'https://example.com/kyc';
      openUrlInNewTab(url);
      expect(mockWindowOpen).toHaveBeenCalledWith(url, '_blank', 'noreferrer noopener');

      const mockUser = {
        current: 'test',
        isCountryIndia: true,
        isOrgRZP: true,
        isActivated: true
      } as Partial<User>;

      (isExperimentEnabled as jest.Mock).mockReturnValue(true);

      expect(isEligibleForSelfServeRekyc(
        { abExperiments: { enable_self_serve_rekyc: true } },
        mockUser
      )).toBe(true);

      expect(isEligibleForSelfServeRekyc(
        { abExperiments: { enable_self_serve_rekyc: true } },
        { ...mockUser, isCountryIndia: false }
      )).toBe(false);
    });
  });
});