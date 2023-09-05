import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import FooterActions from 'common/components/Dropdown/components/FooterActions';

describe('Dropdown - FooterActions', () => {
  const props = {
    tempSelectedOptions: ['option1', 'option2'],
    onClear: jest.fn(),
    onApply: jest.fn(),
  };

  test('should renders two buttons', () => {
    render(<FooterActions {...props} />);
    expect(screen.getByRole('button', { name: 'Apply' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Clear' })).toBeInTheDocument();
  });

  test('should call onClear when Clear button is clicked', async () => {
    render(<FooterActions {...props} />);
    await userEvent.click(screen.getByRole('button', { name: 'Clear' }));
    expect(props.onClear).toHaveBeenCalled();
  });

  test('should call onApply when Apply button is clicked', async () => {
    render(<FooterActions {...props} />);
    await userEvent.click(screen.getByRole('button', { name: 'Apply' }));
    expect(props.onApply).toHaveBeenCalled();
  });

  test('should disables Apply button when no options are selected', () => {
    render(<FooterActions {...props} tempSelectedOptions={[]} />);
    expect(screen.getByRole('button', { name: 'Apply' })).toBeDisabled();
  });

  test('should disable Clear button when no options are selected', () => {
    render(<FooterActions {...props} tempSelectedOptions={[]} />);
    expect(screen.getByRole('button', { name: 'Clear' })).toBeDisabled();
  });
});
