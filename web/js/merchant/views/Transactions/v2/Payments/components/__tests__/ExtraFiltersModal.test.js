import ExtraFiltersModal from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/ExtraFiltersModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, userEvent } from 'test-utils';

const mockHandleSearch = jest.fn();

const renderExtraFiltersModal = () => {
  const defaultPtrops = {
    handleSearch: mockHandleSearch,
  };
  return render(<ExtraFiltersModal {...defaultPtrops} />);
};

describe('ExtraFiltersModal', () => {
  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');
  beforeEach(() => {
    closeModalSpy.mockClear();
  });

  test('should render Payment Method and Channel filters', () => {
    renderExtraFiltersModal();
    expect(screen.getByText('Payment Method')).toBeInTheDocument();
    expect(screen.getByText('Channel')).toBeInTheDocument();
  });

  test('should update Payment Method option to "Card" when selected', async () => {
    renderExtraFiltersModal();

    const dropdownTrigger = screen.getByPlaceholderText('Select Payment Method');

    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);

    const cardOption = screen.getByRole('option', { name: 'Card' });
    expect(cardOption).toBeInTheDocument();
    await userEvent.click(cardOption);

    const selectedOption = screen.getByPlaceholderText('Select Payment Method');
    expect(selectedOption).toHaveValue('Card');

    expect(selectedOption).toHaveTextContent('Card');
  });

  test('should update Source Channel option to "In Person" when Selected', async () => {
    renderExtraFiltersModal();
    const paymentSourceChannel = screen.getByPlaceholderText('Select Source Channel');
    expect(paymentSourceChannel).toBeInTheDocument();
    await userEvent.click(paymentSourceChannel);

    const inPersonOption = screen.getByRole('option', { name: 'In Person' });
    expect(inPersonOption).toBeInTheDocument();
    await userEvent.click(inPersonOption);

    const selectedOption = screen.getByPlaceholderText('Select Source Channel');
    expect(selectedOption).toHaveValue('In Person');

    expect(selectedOption).toHaveTextContent('In Person');
  });

  test('should do a search query for transactions with applied filters upon clicking Apply', async () => {
    renderExtraFiltersModal();
    const dropdownTrigger = screen.getByPlaceholderText('Select Payment Method');
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);

    const cardOption = screen.getByRole('option', { name: 'Card' });
    expect(cardOption).toBeInTheDocument();
    await userEvent.click(cardOption);

    const paymentSourceChannel = screen.getByPlaceholderText('Select Source Channel');
    await userEvent.click(paymentSourceChannel);

    const inPersonOption = screen.getByRole('option', { name: 'In Person' });
    expect(inPersonOption).toBeInTheDocument();
    await userEvent.click(inPersonOption);

    const applyButton = screen.getByRole('button', { name: 'Apply' });
    expect(applyButton).toBeInTheDocument();
    await userEvent.click(applyButton);

    expect(mockHandleSearch).toHaveBeenCalledWith({
      method: 'card',
      source_channel: 'in_person',
    });
  });

  test('should call closeModal on Cancel', async () => {
    renderExtraFiltersModal();
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelButton);
    expect(closeModalSpy).toHaveBeenCalled();
  });
});
