import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ExitPopup from '../SaveAndExitModal';
import { render, screen } from 'test-utils';

test('Exit Popup', () => {
  const buttonText = 'Continue filling Details';
  const App = () => <ExitPopup isOpen={true} onClose={() => {}} exitToDashBoardLink="" />;
  render(<App />, {});
  expect(screen.getByText(buttonText)).toBeInTheDocument();
  expect(screen.getByText('Are you sure you want to exit?')).toBeInTheDocument();
  expect(
    screen.getByText(
      'You are just few steps away. Fill the remaining details and start accepting payments now',
    ),
  ).toBeInTheDocument();
  expect(screen.getByText('Exit to dashboard')).toBeInTheDocument();
});
