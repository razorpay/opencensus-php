import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

// component
import EnableNon3dsStatusModal from '../EnableNon3dsStatusModal';

describe('<EnableNon3dsStatusModal />', () => {
  test('should render without breaking', () => {
    render(<EnableNon3dsStatusModal />);

    expect(render(<EnableNon3dsStatusModal />)).toBeDefined();
  });

  test('should open the modal', () => {
    const { container } = render(<EnableNon3dsStatusModal open />);

    const h3 = container.querySelector('.non-3ds-modal-header > h3');

    expect(h3).toHaveTextContent('Enable non 3D Secure Cards');
  });

  test('should close the modal', () => {
    const onClose = jest.fn();
    const { getByText } = render(<EnableNon3dsStatusModal open onClose={onClose} />);

    fireEvent.click(getByText('Cancel'));

    expect(onClose).toHaveBeenCalled();
  });

  test('should disable request button', () => {
    const { getByText } = render(<EnableNon3dsStatusModal open isLoading />);

    expect(getByText('Request')).toBeDisabled();
  });

  test('should trigger onEnable prop', async () => {
    const onEnable = jest.fn();
    const { findByRole, getByText } = render(<EnableNon3dsStatusModal open onEnable={onEnable} />);

    fireEvent.click(await findByRole('checkbox'));

    const requestBtn = getByText('Request');

    expect(requestBtn).not.toBeDisabled();

    fireEvent.click(requestBtn);

    expect(onEnable).toHaveBeenCalled();
  });
});
