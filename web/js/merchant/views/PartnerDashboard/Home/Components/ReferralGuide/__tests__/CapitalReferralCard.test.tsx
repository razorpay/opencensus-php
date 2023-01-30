import React from 'react';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import '@testing-library/jest-dom/extend-expect';
import { CapitalReferralCard } from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/CapitalReferralCard';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';

describe('CapitalReferralCard', () => {
  const renderApp = ({ handleClick }) => {
    return render(<CapitalReferralCard handleReferClient={handleClick} />);
  };

  test('should render with all the components', () => {
    const handleClick = jest.fn();
    renderApp({ handleClick });

    const heading = screen.getByText((content, node) => {
      const hasText = (node: TODO_PD) =>
        node.textContent === 'Now refer for RazorpayX Corporate Card.';
      const isNodeHasText = hasText(node);
      return isNodeHasText;
    });
    expect(heading).toBeInTheDocument();

    const detailCurrent = screen.getByText('Refer merchants to RazorpayX Current Account');
    expect(detailCurrent).toBeInTheDocument();

    const detailCorporate = screen.getByText('Refer merchants to RazorpayX Corporate Credit Cards');
    expect(detailCorporate).toBeInTheDocument();

    const referButton = screen.getByRole('button', { name: 'Refer Now' });
    expect(referButton).toBeInTheDocument();

    const backgroundImage = screen.getByAltText('rupee');
    expect(backgroundImage).toBeInTheDocument();
  });

  test('should handle button click', async () => {
    const handleClick = jest.fn();
    renderApp({ handleClick });

    const referButton = screen.getByRole('button', { name: 'Refer Now' });
    expect(referButton).toBeInTheDocument();
    await userEvent.click(referButton);

    await waitFor(() => {
      expect(handleClick).toBeCalled();
    });
  });
});
