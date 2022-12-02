import { Provider } from 'react-redux';

import { render, screen } from 'common/services/test/test-utils';

import { storeWithInitialState } from 'merchant/store';

import MaskedEmail from 'merchant/components/Mask/Email';
import { getMaskedEmail } from 'merchant/components/Mask/utils/masking';

const email = 'test@razorpay.com';
const maskedEmail = getMaskedEmail(email);

describe('merchant/components/Mask/Email', () => {
  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <MaskedEmail {...rest} />
      </Provider>
    );
  };

  test('should render email when isHidePIDetails is false', () => {
    render(<App initialState={{ session: { user: { isHidePIDetails: false } } }} email={email} />);

    expect(screen.getByText(email)).toBeInTheDocument();
  });

  test('should render masked email when isHidePIDetails is true', () => {
    render(<App initialState={{ session: { user: { isHidePIDetails: true } } }} email={email} />);

    expect(screen.getByText(maskedEmail)).toBeInTheDocument();
  });
});
