import { Provider } from 'react-redux';

import { render, screen } from 'common/services/test/test-utils';
import MaskedContact, { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';
import { getMaskedContact } from 'merchant/components/Mask/utils/masking';
import { storeWithInitialState } from 'merchant/store';

const contact = '+911234567890';
const maskedContact = getMaskedContact(contact);

describe('merchant/components/Mask/Contact', () => {
  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <MaskedContact {...rest} />
      </Provider>
    );
  };

  test('should render contact when isHidePIDetails is false', () => {
    render(
      <App initialState={{ session: { user: { isHidePIDetails: false } } }} contact={contact} />,
    );

    expect(screen.getByText(contact)).toBeInTheDocument();
  });

  test('should render masked contact when isHidePIDetails is true', () => {
    render(
      <App initialState={{ session: { user: { isHidePIDetails: true } } }} contact={contact} />,
    );

    expect(screen.getByText(maskedContact)).toBeInTheDocument();
  });
});

describe('merchant/components/Mask/getI18FormattedPhoneNumber', () => {
  test('should return input value when not formatted', () => {
    const inputContact = 'invalid_contact';
    const result = getI18FormattedPhoneNumber(inputContact);
    expect(result).toBe(inputContact);
  });

  test('should return input value when utility throws error', () => {
    const inputContact = '';
    const result = getI18FormattedPhoneNumber(inputContact);
    expect(result).toBe(inputContact);
  });

  test('should format input contact number', () => {
    const inputContact = '+917777777777';
    const result = getI18FormattedPhoneNumber(inputContact);
    expect(result).toBe('+91 7777 777777');
  });
});
