import { render } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

// component
import Non3dsStatusInfo from 'merchant/views/Settings/PaymentMethods/components/Non3dsCardsActivation/Non3dsStatusInfo';

describe('<Non3dsStatusInfo />', () => {
  test('should render without breaking', () => {
    const { container } = render(<Non3dsStatusInfo />);

    expect(container.getElementsByClassName('non-3ds-status-info')).toBeDefined();
  });
});
