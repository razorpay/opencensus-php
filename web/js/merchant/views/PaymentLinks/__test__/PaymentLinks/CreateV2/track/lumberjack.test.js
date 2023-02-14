import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track/lumberjack';

describe('Payment Link CreateV2 Lumberjack Track - UT', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  const lumberjackMock = jest.fn();
  track.init({ track: lumberjackMock });

  test('should call send with "linkTypeSelection" variation', () => {
    track.linkTypeSelection.open();
    track.linkTypeSelection.select('pl');
    expect(lumberjackMock).toHaveBeenCalledTimes(2);
  });

  test('should call send with "fields" variation', () => {
    track.fields.currency();
    track.fields.amount();
    track.fields.paymentFor();
    track.fields.partialPayment();
    track.fields.firstPaymentMinAmount();
    track.fields.contact();
    track.fields.email();
    track.fields.notifySms();
    track.fields.notifyEmail();
    track.fields.receipt();
    track.fields.expiryDate();
    track.fields.expiryTime();
    track.fields.notes();
    track.fields.reminders();
    expect(lumberjackMock).toHaveBeenCalledTimes(14);
  });

  test('should call send with "form" variation', () => {
    track.form.cancel();
    track.form.cancelConfirm('pl');
    track.form.create();
    track.form.success();
    track.form.fail('pl');
    expect(lumberjackMock).toHaveBeenCalledTimes(5);
  });
});
