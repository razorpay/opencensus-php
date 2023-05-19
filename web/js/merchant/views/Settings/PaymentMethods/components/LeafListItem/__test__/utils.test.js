import { render, screen } from 'test-utils';

import {
  REJECTED,
  ACTIVATED_ACTION_REQUIRED,
  GREYED,
  ACTIVATED,
  REQUESTED,
  ZESTMONEY,
  GETSIMPL,
} from 'merchant/views/Settings/PaymentMethods/constants';

import {
  getListClass,
  displayName,
  disabledMessagesForInstrument,
} from 'merchant/views/Settings/PaymentMethods/components/LeafListItem/utils';

describe('getListClass', () => {
  test('should return class name action-required-list-item when status is rejected', () => {
    const className = getListClass({ status: REJECTED, path: '' });

    expect(className).toBe('action-required-list-item');
  });

  test('should return class name activated-action-required-list-item when status is activated_action_required', () => {
    const className = getListClass({ status: ACTIVATED_ACTION_REQUIRED, path: '' });

    expect(className).toBe('activated-action-required-list-item');
  });

  test('should return class name list-item-disabled when status is greyed', () => {
    const className = getListClass({ status: GREYED, path: '' });

    expect(className).toBe('list-item-disabled');
  });

  test('should return class name list-item-has-description when status is activated and path is pg.wallet.paytm', () => {
    const className = getListClass({ status: ACTIVATED, path: 'pg.wallet.paytm' });

    expect(className).toBe('list-item-has-description');
  });

  test('should return class name list-item-has-description when status is requested', () => {
    const className = getListClass({ status: REQUESTED, path: '' });

    expect(className).toBe('list-item-has-description');
  });

  test('should return class name list-item-has-long-description when path is pg.upi.google_pay', () => {
    const className = getListClass({ status: '', path: 'pg.upi.google_pay' });

    expect(className).toBe('list-item-has-long-description');
  });

  test('should return class name list-item when path or status is not known', () => {
    const className = getListClass({ status: '', path: '' });

    expect(className).toBe('list-item');
  });

  test('should return class name list-item when empty object is passed', () => {
    const className = getListClass({});

    expect(className).toBe('list-item');
  });
});

describe('displayName', () => {
  test('should return html having data-testid equal to direct-ins-name when there is no intermediate instrument', () => {
    const html = displayName({ name: 'Bank of India', intermediateInstrument: null });

    render(html);

    expect(screen.queryByTestId('direct-ins-name')).toBeInTheDocument();
  });

  test('should return html having data-testid equal to direct-ins-name when there is no intermediate instrument present', () => {
    const html = displayName({
      name: 'Bank of India',
    });

    render(html);

    expect(screen.queryByTestId('direct-ins-name')).toBeInTheDocument();
  });

  test('should return html having data-testid equal to intermediate-ins-name when there is intermediate instrument and intermediate instrument slug is equal to netbanking', () => {
    const html = displayName({
      name: 'Bank of India',
      intermediateInstrument: { slug: 'netbanking' },
    });

    render(html);

    expect(screen.queryByTestId('intermediate-ins-name')).toBeInTheDocument();
  });
});

describe('isAffordabilityDisabledInstrument', () => {
  test('Should return info messgae if instrument is zestmoney', () => {
    expect(disabledMessagesForInstrument(ZESTMONEY, REQUESTED)).toBe(
      `${ZESTMONEY} has temporarily disabled its services. We will keep you updated on when they are resumed.`,
    );
  });

  test('Should return info messgae if instrument is getSimpl', () => {
    expect(disabledMessagesForInstrument(GETSIMPL, REQUESTED)).toBe(
      `${GETSIMPL} has paused onboarding of new merchants. We will keep you updated on when the onboarding resumes.`,
    );
  });

  test('Should not return info messgae if instrument is getSimpl and status is Activated', () => {
    expect(disabledMessagesForInstrument(GETSIMPL, ACTIVATED)).toBeNull();
  });

  test('Should not return info messgae for other providers', () => {
    expect(disabledMessagesForInstrument('HDFC', ACTIVATED)).toBeNull();
  });
});
