import { render } from 'test-utils';

import HelpSection from '../HelpSection';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    on: jest.fn(),
    off: jest.fn(),
  },
  TicketSystemEmitter: {
    on: jest.fn(),
    off: jest.fn(),
    emit: jest.fn(),
  },
}));

describe('HelpSection Component', () => {
  test('should not render Support component for non-Indian users', () => {
    const { getByTestId } = render(
      <HelpSection
        history={{ location: { pathname: '/' } }}
        isHelpWidgetVisible={true}
        user={{
          isINCountry: false,
        }}
      />,
    );
    expect(getByTestId('component-wrapper')).toBeEmptyDOMElement();
  });
});
