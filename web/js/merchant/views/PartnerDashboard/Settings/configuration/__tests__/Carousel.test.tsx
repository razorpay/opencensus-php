import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { Carousel } from 'merchant/views/PartnerDashboard/Settings/configuration/Carousel';

const props = {
  carouselItems: [<div key="1">Hello</div>, <div key="2">World</div>],
};
describe('Carousel', () => {
  test('should render components', () => {
    render(<Carousel {...props} />);

    const buttonElement = screen.getAllByRole('button');
    expect(buttonElement).toHaveLength(2);

    const childrenElement = screen.getByText('Hello');
    expect(childrenElement).toBeInTheDocument();

    const childrenElementWorld = screen.getByText('World');
    expect(childrenElementWorld).toBeInTheDocument();
  });

  test('should handle prev button click', async () => {
    render(<Carousel {...props} />);

    const buttonElement = screen.getAllByRole('button');
    userEvent.click(buttonElement[0]);

    await waitFor(() => {
      const dots = screen.getAllByTestId('dots');
      expect(dots[1]).toHaveClass('active-dot');
    });
  });

  test('should handle next button click', async () => {
    render(<Carousel {...props} />);

    const buttonElement = screen.getAllByRole('button');
    userEvent.click(buttonElement[1]);

    await waitFor(() => {
      const dots = screen.getAllByTestId('dots');
      expect(dots[1]).toHaveClass('active-dot');
    });
  });
});
