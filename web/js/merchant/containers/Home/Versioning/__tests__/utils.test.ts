import { getRows, getGridTemplateColumns, getGridColumn, useDeviceType, shouldOpenVersioningModal, LOCAL_STORAGE_KEY, MAX_COUNT, TWENTY_FOUR_HOURS } from '../utils';
import * as bladeComponents from '@razorpay/blade/components';
import * as bladeUtils from '@razorpay/blade/utils';

jest.mock('@razorpay/blade/components', () => ({
  useTheme: jest.fn(),
}));
jest.mock('@razorpay/blade/utils', () => ({
  useBreakpoint: jest.fn(),
}));

describe('useDeviceType', () => {
  beforeEach(() => {
    (bladeComponents.useTheme as jest.Mock).mockReturnValue({ theme: { breakpoints: {} } });
  });

  it('returns desktop for l and xl breakpoints', () => {
    (bladeUtils.useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'l' });
    expect(useDeviceType()).toBe('desktop');
    (bladeUtils.useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'xl' });
    expect(useDeviceType()).toBe('desktop');
  });

  it('returns tablet for m breakpoint', () => {
    (bladeUtils.useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'm' });
    expect(useDeviceType()).toBe('tablet');
  });

  it('returns mobile for other breakpoints', () => {
    (bladeUtils.useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 's' });
    expect(useDeviceType()).toBe('mobile');
    (bladeUtils.useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: undefined });
    expect(useDeviceType()).toBe('mobile');
  });
});

describe('getGridTemplateColumns', () => {
  it('returns correct columns for desktop', () => {
    expect(getGridTemplateColumns('desktop')).toBe('repeat(4, 1fr)');
  });
  it('returns correct columns for tablet', () => {
    expect(getGridTemplateColumns('tablet')).toBe('repeat(3, 1fr)');
  });
  it('returns correct columns for mobile', () => {
    expect(getGridTemplateColumns('mobile')).toBe('1fr');
  });
});

describe('getGridColumn', () => {
  it('returns correct span for desktop', () => {
    expect(getGridColumn('desktop', 2)).toBe('span 2');
  });
  it('returns correct span for tablet with spanFullTablet', () => {
    expect(getGridColumn('tablet', 2, true)).toBe('1 / -1');
  });
  it('returns correct span for tablet without spanFullTablet', () => {
    expect(getGridColumn('tablet', 2, false)).toBe('span 2');
  });
  it('returns correct span for mobile', () => {
    expect(getGridColumn('mobile', 1)).toBe('span 1');
  });
});

describe('getRows', () => {
  const baseCard = {
    id: 'a',
    title: 'A',
    productName: 'Product A',
    tags: ['tag1'],
    imgLarge: 'imgA_large.png',
    imgSmall: 'imgA_small.png',
    backText: 'Back A',
    ctaText: 'CTA A',
    ctaLink: '/a',
  };
  it('returns empty for empty input', () => {
    expect(getRows('desktop', [])).toEqual([]);
  });
  it('returns correct structure for desktop', () => {
    const sampleCards = [
      { ...baseCard, id: '1' },
      { ...baseCard, id: '2' },
      { ...baseCard, id: '3' },
      { ...baseCard, id: '4' },
    ];
    const rows = getRows('desktop', sampleCards);
    expect(Array.isArray(rows)).toBe(true);
    expect(rows.length).toBeGreaterThan(0);
  });
  it('returns correct structure for tablet', () => {
    const sampleCards = [
      { ...baseCard, id: '1' },
      { ...baseCard, id: '2' },
      { ...baseCard, id: '3' },
      { ...baseCard, id: '4' },
    ];
    const rows = getRows('tablet', sampleCards);
    expect(Array.isArray(rows)).toBe(true);
    expect(rows.length).toBeGreaterThan(0);
  });
  it('returns correct structure for mobile', () => {
    const sampleCards = [
      { ...baseCard, id: '1' },
      { ...baseCard, id: '2' },
      { ...baseCard, id: '3' },
      { ...baseCard, id: '4' },
    ];
    const rows = getRows('mobile', sampleCards);
    expect(Array.isArray(rows)).toBe(true);
    expect(rows.length).toBeGreaterThan(0);
  });

  it('desktop: last row with 2 cards should both be large', () => {
    const cards = [
      { ...baseCard, id: '1' },
      { ...baseCard, id: '2' },
      { ...baseCard, id: '3' },
      { ...baseCard, id: '4' },
      { ...baseCard, id: '5' },
    ]; // 5 cards: 3 in first row, 2 in last row
    const rows = getRows('desktop', cards);
    const lastTwo = rows.slice(-2);
    expect(lastTwo.every(card => card.boxType === 'large')).toBe(true);
    expect(lastTwo.every(card => card.span === 2)).toBe(true);
  });

  it('tablet: last row with 1 card should span full width', () => {
    const cards = [
      { ...baseCard, id: '1' },
      { ...baseCard, id: '2' },
      { ...baseCard, id: '3' },
    ]; // 3 cards: 2 in first row, 1 in last row
    const rows = getRows('tablet', cards);
    const last = rows[rows.length - 1];
    expect(last.spanFullTablet).toBe(true);
    expect(last.boxType).toBe('large');
    expect(last.span).toBe(2);
  });
});

describe('shouldOpenVersioningModal', () => {
  let originalLocalStorage: Storage;
  let dateNowSpy: jest.SpyInstance;

  beforeEach(() => {
    // @ts-ignore
    originalLocalStorage = global.localStorage;
    let store: Record<string, string> = {};
    global.localStorage = {
      getItem: (key: string) => store[key] || null,
      setItem: (key: string, value: string) => { store[key] = value; },
      removeItem: (key: string) => { delete store[key]; },
      clear: () => { store = {}; },
      key: (i: number) => Object.keys(store)[i] || null,
      length: 0,
    } as any;
    localStorage.clear();
  });

  afterEach(() => {
    // @ts-ignore
    global.localStorage = originalLocalStorage;
    if (dateNowSpy) dateNowSpy.mockRestore();
    jest.restoreAllMocks();
  });

  function makeSearchParams(params: Record<string, string>) {
    const usp = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => usp.set(k, v));
    return usp;
  }

  it('returns true if localStorage key is missing', () => {
    dateNowSpy = jest.spyOn(Date, 'now').mockReturnValue(1000000);
    const result = shouldOpenVersioningModal('/dashboard', makeSearchParams({}));
    expect(result).toEqual({ shouldOpen: true, runModalOpenSideEffects: expect.any(Function) });
  });

  it('returns true if count < MAX_COUNT and expired', () => {
    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify({ count: 1, expiresAt: 0 }));
    dateNowSpy = jest.spyOn(Date, 'now').mockReturnValue(1000001);
    const result = shouldOpenVersioningModal('/dashboard', makeSearchParams({}));
    expect(result).toEqual({ shouldOpen: true, runModalOpenSideEffects: expect.any(Function) });
  });

  it('returns false if count >= MAX_COUNT', () => {
    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify({ count: MAX_COUNT, expiresAt: 0 }));
    dateNowSpy = jest.spyOn(Date, 'now').mockReturnValue(1000001);
    const result = shouldOpenVersioningModal('/dashboard', makeSearchParams({}));
    expect(result).toEqual({ shouldOpen: false });
  });

  it('returns true if query params trigger modal', () => {
    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify({ count: MAX_COUNT, expiresAt: 0 }));
    dateNowSpy = jest.spyOn(Date, 'now').mockReturnValue(1000001);
    const result = shouldOpenVersioningModal('/dashboard', makeSearchParams({ event: 'versioning' }));
    expect(result).toEqual({ shouldOpen: true, isQueryParam: true });
  });

  it('returns false if not expired and count < MAX_COUNT', () => {
    const now = Date.now();
    const expiresAt = now + 5 * 60 * 1000; // 5 minutes from now
    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify({ count: 1, expiresAt }));

    const result = shouldOpenVersioningModal('/dashboard', makeSearchParams({}));

    expect(result).toEqual({ shouldOpen: false });
  });
}); 