// testable
import PurposeCodeIneligiblePopup from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/PurposeCodeIneligiblePopup';
///- testable

// utils
import * as Actions from 'merchant_common/reducers/modals';
import * as B2bActions from 'merchant/reducers/b2bExports/actions';
import { render, screen, userEvent } from 'test-utils';
///- utils

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

jest.mock('merchant/reducers/b2bExports/actions', () => {
  return {
    closePurposeCodeIneligibleModal: jest.fn(() => (dispatch) => {
      dispatch({
        type: 'B2B_CLOSE_PURPOSE_CODE_INELIGIBLE_MODAL',
      });
    }),
  };
});

describe('PurposeCodeIneligiblePopup', () => {
  beforeEach(() => {
    Actions.closeModal.mockClear();
    Actions.openModal.mockClear();
    B2bActions.closePurposeCodeIneligibleModal.mockClear();
  });

  it('should render the component correctly', () => {
    render(<PurposeCodeIneligiblePopup code="someCode" />);

    expect(screen.getByText('Ineligible Purpose Code')).toBeInTheDocument();
    expect(
      screen.getByText(
        'The purpose code you have provided is incorrect as per your category of business. Update your purpose code to settle your transactions.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Update purpose code')).toBeInTheDocument();
  });

  it('should call onCloseAction and onClose functions when closing the popup', async () => {
    render(<PurposeCodeIneligiblePopup code="someCode" />);

    await userEvent.click(screen.getByTestId('modal-header-close-btn'));

    expect(Actions.closeModal).toHaveBeenCalledTimes(1);
    expect(B2bActions.closePurposeCodeIneligibleModal).toHaveBeenCalledTimes(1);
  });

  it('should call onCloseAction, onClose, and onOpen functions when clicking on "Update purpose code"', async () => {
    render(<PurposeCodeIneligiblePopup code="someCode" />);

    await userEvent.click(screen.getByText('Update purpose code'));

    expect(B2bActions.closePurposeCodeIneligibleModal).toHaveBeenCalledTimes(1);
    expect(Actions.closeModal).toHaveBeenCalledTimes(1);
    expect(Actions.openModal).toHaveBeenCalledTimes(1);
    expect(Actions.openModal).toHaveBeenCalledWith({
      size: 'medium',
      component: expect.any(Object),
    });
  });
});
