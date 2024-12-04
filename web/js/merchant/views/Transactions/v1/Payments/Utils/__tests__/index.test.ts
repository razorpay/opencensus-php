import { screen, render } from 'common/services/test/test-utils';
import { _paymentId } from '..';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

jest.mock('common/utils/selfServeAnalytics', () => ({
  selfServeTrackInitiate: jest.fn(),
}));

describe('Validate: _paymentId', () => {
  test('Should render with correct entity', () => {
    const mockItem = {
      id: '12345',
      entity: 'payment',
    };

    render(_paymentId(mockItem, 'Payment Details'));
    const linkElem = screen.getByText(mockItem.id);
    expect(linkElem).toBeInTheDocument();
    expect(linkElem.closest('a')).toHaveAttribute(
      'href',
      `/payments/${mockItem.id}?init_point=payments-table&init_page=Payment Details`,
    );
  });

  test('Should call track event on click', () => {
    const mockItem = {
      id: '12345',
      entity: 'payment',
    };

    render(_paymentId(mockItem, 'Payment Details'));
    const linkElem = screen.getByText(mockItem.id);
    linkElem.click();
    expect(selfServeTrackInitiate).toHaveBeenCalled();
  });
});
