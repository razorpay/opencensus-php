import React, { useState } from 'react';
import '@testing-library/jest-dom/extend-expect';
import Modal from '../Modal';
import { render, fireEvent, screen } from 'test-utils';

test('Modal rendering', () => {
  const buttonText = 'Click Me';
  const modalBodyText = 'Modal Body';

  const App = () => {
    const [isOpen, setIsOpen] = useState(false);
    return (
      <>
        <button onClick={() => setIsOpen(true)}> {buttonText} </button>
        <Modal isOpen={isOpen} onClose={() => setIsOpen(false)}>
          {modalBodyText}
        </Modal>
      </>
    );
  };
  render(<App />, {});
  expect(screen.getByText(buttonText)).toBeInTheDocument();
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
  fireEvent.click(screen.getByText(buttonText));
  expect(screen.queryByText(modalBodyText)).toBeInTheDocument();
  fireEvent.click(screen.getByTestId('modalCloseButton'));
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
});
