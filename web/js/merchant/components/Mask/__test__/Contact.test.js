import { Provider } from 'react-redux';

import { render, screen } from 'common/services/test/test-utils';

import { storeWithInitialState } from 'merchant/store';

import MaskedContact from 'merchant/components/Mask/Contact';
import { getMaskedContact } from 'merchant/components/Mask/utils/masking';

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
