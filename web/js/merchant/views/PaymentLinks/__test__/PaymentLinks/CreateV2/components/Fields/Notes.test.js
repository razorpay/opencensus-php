import React from 'react';
import { render, screen } from 'test-utils';
import Notes from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/Notes';

describe('Notes', () => {
  test('renders a pair list of notes', () => {
    render(<Notes />);
    const notesLabel = screen.getByText('Notes');
    expect(notesLabel).toBeInTheDocument();
  });

  test('should render add new CTA', () => {
    render(<Notes />);
    const addNewCTA = screen.getByRole('button', {
      name: /Add New/i,
    });
    expect(addNewCTA).toBeInTheDocument();
  });
});
