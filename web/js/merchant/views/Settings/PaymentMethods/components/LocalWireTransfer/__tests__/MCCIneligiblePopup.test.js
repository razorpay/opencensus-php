import MCCIneligiblePopup from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/MCCIneligiblePopup';
import * as Actions from 'merchant_common/reducers/modals';
import { render, screen, userEvent } from 'test-utils';

// mocks
jest.mock('merchant_common/reducers/modals', () => {
  return {
    openModal: jest.fn(() => (dispatch) => {
      dispatch({
        type: 'OPEN_MODAL',
      });
    }),
    closeModal: jest.fn(() => (dispatch) => {
      dispatch({
        type: 'CLOSE_MODAL',
      });
    }),
  };
});

describe('MCCIneligiblePopup', () => {
  beforeEach(() => {
    Actions.closeModal.mockClear();
    Actions.openModal.mockClear();
  });

  test('should render the component correctly', () => {
    render(<MCCIneligiblePopup error="MCC code is not eligible" />);

    expect(screen.getByText('Ineligible Merchant Category')).toBeInTheDocument();
    expect(screen.getByText('MCC code is not eligible')).toBeInTheDocument();
    expect(screen.getByText('Close')).toBeInTheDocument();
  });

  test('should call onCloseAction and onClose functions when closing the popup', async () => {
    render(<MCCIneligiblePopup code="someCode" />);

    await userEvent.click(screen.getByTestId('modal-header-close-btn'));

    expect(Actions.closeModal).toHaveBeenCalledTimes(1);
  });
});
