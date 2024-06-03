import {
  getIssuerLabel,
  isDebitCardIssuer,
  updateOfferDataFormat,
} from 'merchant/views/Offers/utils';

describe('Test getIssuerLabel', () => {
  test('Payment network as issuer', () => {
    const visaLabel = getIssuerLabel('VISA');
    expect(visaLabel).toBe('Visa');

    const amexLabel = getIssuerLabel('AMEX');
    expect(amexLabel).toBe('American Express');
  });

  test('Normal issuers as issuer', () => {
    const hdfcLabel = getIssuerLabel('HDFC');
    expect(hdfcLabel).toBe('HDFC Bank');

    const paypalLabel = getIssuerLabel('paypal');
    expect(paypalLabel).toBe('Paypal');
  });

  test('Debit card Issuers as issuers', () => {
    const indbDebitCardLabel = getIssuerLabel('INDB_DC');
    expect(indbDebitCardLabel).toBe('INDUSIND Bank Debit Card');

    const kkbkDebitCardLabel = getIssuerLabel('KKBK_DC');
    expect(kkbkDebitCardLabel).toBe('Kotak Mahindra Bank Debit Card');
  });
});

describe('Test isDebitCardIssuer', () => {
  test('With issuer as debit card', () => {
    const isDebitCard = isDebitCardIssuer('HDFC_DC');
    expect(isDebitCard).toBe(true);
  });

  test('With non debit card issuer', () => {
    const isDebitCard = isDebitCardIssuer('HDFC');
    expect(isDebitCard).toBe(false);
  });

  test('With null issuer', () => {
    const isDebitCard = isDebitCardIssuer(null);
    expect(isDebitCard).toBe(false);
  });

  test('Without issuer', () => {
    const isDebitCard = isDebitCardIssuer();
    expect(isDebitCard).toBe(false);
  });
});

describe('Test updateOfferDataFormat', () => {
  test('offerData with issuer as credit card', () => {
    const offerData = {
      issuer: 'HDFC',
      payment_method_type: 'credit',
    };

    const newOfferData = updateOfferDataFormat(offerData);

    expect(newOfferData.issuer).toBe('HDFC');
    expect(newOfferData.payment_method_type).toBe('credit');
  });

  test('offerData with issuer as debit card with `_DC`', () => {
    const offerData = {
      issuer: 'HDFC_DC',
    };

    const newOfferData = updateOfferDataFormat(offerData);

    expect(newOfferData.issuer).toBe('HDFC');
    expect(newOfferData.payment_method_type).toBe('debit');
  });

  test('offerData with issuer as debit card without `_DC`', () => {
    const offerData = {
      issuer: 'HDFC',
      payment_method_type: 'debit',
    };

    const newOfferData = updateOfferDataFormat(offerData);

    expect(newOfferData.issuer).toBe('HDFC');
    expect(newOfferData.payment_method_type).toBe('debit');
  });
});
