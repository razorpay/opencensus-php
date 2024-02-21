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

    const dropdownTrigger = screen.getByRole('combobox');
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);

    const cardOption = screen.getByRole('option', { name: 'Card' });
    expect(cardOption).toBeInTheDocument();
    await userEvent.click(cardOption);

    const selectedOption = screen.getByRole('combobox');
    expect(selectedOption).toHaveValue('Card');

    expect(selectedOption).toHaveTextContent('Card');
  });

  test('should render all channel options', () => {
    renderExtraFiltersModal();
    expect(screen.getByRole('radio', { name: 'In Person' })).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'Online' })).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'All' })).toBeInTheDocument();
  });

  test('should do a search query for transactions with applied filters upon clicking Apply', async () => {
    renderExtraFiltersModal();
    const dropdownTrigger = screen.getByRole('combobox');
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);

    const cardOption = screen.getByRole('option', { name: 'Card' });
    expect(cardOption).toBeInTheDocument();
    await userEvent.click(cardOption);

    const inPersonSourceChannel = screen.getByRole('radio', { name: 'In Person' });
    expect(inPersonSourceChannel).toBeInTheDocument();
    await userEvent.click(inPersonSourceChannel);

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
