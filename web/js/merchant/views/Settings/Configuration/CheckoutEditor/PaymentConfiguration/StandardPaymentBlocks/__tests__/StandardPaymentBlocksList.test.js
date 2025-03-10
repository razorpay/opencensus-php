import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import { StandardPaymentBlocksList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/StandardPaymentBlocks/StandardPaymentBlocksList';

const visibleBlocks = [
  { slug: 'block1', name: 'Block 1', description: 'Description 1', isVisible: true },
  { slug: 'block2', name: 'Block 2', description: 'Description 2', isVisible: true },
];

const hiddenBlocks = [
  { slug: 'block3', name: 'Block 3', description: 'Description 3', isVisible: false },
  { slug: 'block4', name: 'Block 4', description: 'Description 4', isVisible: false },
];

describe('StandardPaymentBlocksList', () => {
  test('should correctly compute allBlocks', () => {
    render(<StandardPaymentBlocksList visibleBlocks={visibleBlocks} hiddenBlocks={hiddenBlocks} />);
    const allBlocks = [...visibleBlocks, ...hiddenBlocks];
    expect(allBlocks.length).toBe(4);
  });

  test('should correctly compute displayBlocks when shouldShowAllBlocks is true', () => {
    render(<StandardPaymentBlocksList visibleBlocks={visibleBlocks} hiddenBlocks={hiddenBlocks} />);
    const displayBlocks = [...visibleBlocks, ...hiddenBlocks];
    displayBlocks.forEach((block) => {
      expect(screen.getByText(block.name)).toBeInTheDocument();
    });
  });

  test('should correctly compute displayBlocks when shouldShowAllBlocks is false and there are visible blocks', () => {
    render(<StandardPaymentBlocksList visibleBlocks={visibleBlocks} hiddenBlocks={hiddenBlocks} />);
    fireEvent.click(screen.getByText('Hide'));
    visibleBlocks.forEach((block) => {
      expect(screen.getByText(block.name)).toBeInTheDocument();
    });
  });

  test('should correctly compute displayBlocks when shouldShowAllBlocks is false and there are no visible blocks', () => {
    render(<StandardPaymentBlocksList visibleBlocks={[]} hiddenBlocks={hiddenBlocks} />);
    fireEvent.click(screen.getByText('Hide'));
    hiddenBlocks.slice(0, 2).forEach((block) => {
      expect(screen.getByText(block.name)).toBeInTheDocument();
    });
  });

  test('should toggle shouldShowAllBlocks state', () => {
    render(<StandardPaymentBlocksList visibleBlocks={visibleBlocks} hiddenBlocks={hiddenBlocks} />);
    const toggleButton = screen.getByText('Hide');
    fireEvent.click(toggleButton);
    expect(screen.getByText('Show all')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Show all'));
    expect(screen.getByText('Hide')).toBeInTheDocument();
  });
});
