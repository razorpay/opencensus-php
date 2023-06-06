import { getButtonThemes } from 'merchant/views/PaymentButton/PaymentButton/Create/constants/buttonThemes';

describe('Button Themes', () => {
  test('Curlec themes buttons ', () => {
    const businessName = 'Curlec';
    const buttonThemes = getButtonThemes(businessName);

    const btnDarkStandardLabel = buttonThemes.BTN_DARK_STANDARD.label;
    const btnDarkStandardClass = buttonThemes.BTN_DARK_STANDARD.value;
    expect(btnDarkStandardLabel).toBe('Curlec Dark');
    expect(btnDarkStandardClass).toBe('rzp-dark-standard');

    const btnOutlineStandardLabel = buttonThemes.BTN_OUTLINE_STANDARD.label;
    const btnOutlineStandardClass = buttonThemes.BTN_OUTLINE_STANDARD.value;
    expect(btnOutlineStandardLabel).toBe('Curlec Outline');
    expect(btnOutlineStandardClass).toBe('rzp-outline-standard');
  });

  test('Razorpay themes buttons ', () => {
    const buttonThemes = getButtonThemes();

    const btnDarkStandardLabel = buttonThemes.BTN_DARK_STANDARD.label;
    const btnDarkStandardClass = buttonThemes.BTN_DARK_STANDARD.value;
    expect(btnDarkStandardLabel).toBe('Razorpay Dark');
    expect(btnDarkStandardClass).toBe('rzp-dark-standard');

    const btnOutlineStandardLabel = buttonThemes.BTN_OUTLINE_STANDARD.label;
    const btnOutlineStandardClass = buttonThemes.BTN_OUTLINE_STANDARD.value;
    expect(btnOutlineStandardLabel).toBe('Razorpay Outline');
    expect(btnOutlineStandardClass).toBe('rzp-outline-standard');
  });
});
