import { render } from '@testing-library/react';

// constants
import { NON_3DS_CARD_WORKFLOW_STATUS } from 'merchant/reducers/non3dsCardsActivation';

// component
import Non3dsStatusDescription from '../Non3dsStatusDescription';

describe('<Non3dsStatusDescription />', () => {
  test('Should return disabled description for closed workflow status', () => {
    const { container } = render(
      <Non3dsStatusDescription status={NON_3DS_CARD_WORKFLOW_STATUS.CLOSED} showLearnMoreLink />,
    );

    expect(container.querySelector('div')).toHaveTextContent(
      'Non 3D Secure cards are unauthenticated cards that might have fraud risks. Non 3D Secure transactions are disabled for your account. If you think, this is done by mistake, please request to enable it.',
    );
  });
  test('Should return enabled description for approved workflow status', () => {
    const { container } = render(
      <Non3dsStatusDescription status={NON_3DS_CARD_WORKFLOW_STATUS.APPROVED} showLearnMoreLink />,
    );

    expect(container.querySelector('div')).toHaveTextContent(
      'Non 3D Secure cards are unauthenticated cards that might have fraud risks. Learn More',
    );
  });
  test('Should return requsted for open workflow status', () => {
    const { container } = render(
      <Non3dsStatusDescription status={NON_3DS_CARD_WORKFLOW_STATUS.OPEN} showLearnMoreLink />,
    );

    expect(container.querySelector('div')).toHaveTextContent(
      'Non 3D Secure cards are unauthenticated cards that might have fraud risks',
    );
  });
  test('Should return default for invalid workflow status', () => {
    const { container } = render(<Non3dsStatusDescription status="invalid" showLearnMoreLink />);

    expect(container.querySelector('div')).toHaveTextContent(
      'Non 3D Secure cards are unauthenticated cards that might have fraud risks. Learn More',
    );
  });
  test('Should not show learn more link', () => {
    const { container } = render(<Non3dsStatusDescription status="invalid" />);

    expect(container.querySelector('div')).toHaveTextContent(
      'Non 3D Secure cards are unauthenticated cards that might have fraud risks.',
    );
  });
});
