import React, { useState } from 'react';
import '@testing-library/jest-dom/extend-expect';
import Modal from 'common/components/Modal/Modal';
import { ModalHeader, ModalBody, ModalFooter } from 'common/components/Modal/Styled';
import { render, fireEvent, screen } from 'test-utils';

const buttonText = 'Click Me';
const closeModal = 'Click Here';
const modalBodyText = 'Modal Body';

const ModalApp = ({ isOpen, onClose, onClick, closeable = true, bottomsheet = false }) => {
  return (
    <>
      <button onClick={onClick}>{buttonText}</button>
      <Modal isOpen={isOpen} onClose={onClose} closeable={closeable} bottomsheet={bottomsheet}>
        <ModalHeader>Default Modal Header</ModalHeader>
        <ModalBody>{modalBodyText}</ModalBody>
        <ModalFooter>
          <button onClick={onClose}>{closeModal}</button>
        </ModalFooter>
      </Modal>
    </>
  );
};

test('Modal rendering', () => {
  const App = () => {
    const [isOpen, setIsOpen] = useState(false);
    return (
      <ModalApp isOpen={isOpen} onClose={() => setIsOpen(false)} onClick={() => setIsOpen(true)} />
    );
  };
  render(<App />, {});
  expect(screen.getByText(buttonText)).toBeInTheDocument();
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
  fireEvent.click(screen.getByText(buttonText));
  expect(screen.queryByText(modalBodyText)).toBeInTheDocument();
  expect(screen.queryByText(closeModal)).toBeInTheDocument();
  fireEvent.click(screen.getByText(closeModal));
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
});

test('Modal should not be close by using of icon button', () => {
  const App = () => {
    const [isOpen, setIsOpen] = useState(false);
    return (
      <ModalApp
        isOpen={isOpen}
        onClose={() => setIsOpen(false)}
        onClick={() => setIsOpen(true)}
        closeable={false}
      />
    );
  };
  render(<App />, {});
  expect(screen.getByText(buttonText)).toBeInTheDocument();
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
  fireEvent.click(screen.getByText(buttonText));
  expect(screen.queryByText(modalBodyText)).toBeInTheDocument();
  expect(() => screen.getByTestId('modalCloseButton')).toThrow();
  fireEvent.click(screen.getByText(closeModal));
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
});

test('Modal should be close post outside Modal body click', () => {
  const App = () => {
    const [isOpen, setIsOpen] = useState(false);
    return (
      <ModalApp
        isOpen={isOpen}
        onClose={() => setIsOpen(false)}
        onClick={() => setIsOpen(true)}
        bottomsheet={true}
      />
    );
  };
  render(<App />, {});
  expect(screen.getByText(buttonText)).toBeInTheDocument();
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
  fireEvent.click(screen.getByText(buttonText));
  expect(screen.queryByText(modalBodyText)).toBeInTheDocument();
  expect(screen.queryByText(closeModal)).toBeInTheDocument();
  fireEvent.click(screen.getByTestId('layerTestId'));
  expect(screen.queryByText(modalBodyText)).not.toBeInTheDocument();
});
