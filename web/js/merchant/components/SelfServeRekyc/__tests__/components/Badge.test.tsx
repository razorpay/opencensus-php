import React from 'react';
import { render, screen } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import BadgeComponent from 'merchant/components/SelfServeRekyc/components/Badge';
import { FeedbackColors } from 'merchant/components/SelfServeRekyc/types';

describe('BadgeComponent', () => {
  const defaultProps = {
    daysFromDeadline: 7,
    chipType: 'negative' as FeedbackColors,
    badgeText: undefined
  };

  it('renders days from deadline when badgeText is not provided', () => {
    render(<BadgeComponent {...defaultProps} />);
    expect(screen.getByText('7 days left')).toBeInTheDocument();
  });

  it('renders custom badge text when provided', () => {
    const customText = 'Action Required';
    render(<BadgeComponent {...defaultProps} badgeText={customText} />);
    expect(screen.getByText(customText)).toBeInTheDocument();
  });
});