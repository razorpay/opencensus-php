import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import ActivationStep from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide/ActivationStep';

// TODO: only basic render test added, other tests can be added later.

const defaultProps = {
  isCurrentStep: true,
  isNextStep: false,
  isCompletedStep: false,
  isFailedStep: false,
  stepContent: {
    title: 'step title',
    subTitle: 'step subtitle',
    ctaText: 'step CTA',
    toolTip: null,
    onClickCTA: jest.fn(),
    stepName: 'start-referring',
  },
};

describe('ActivationStep', () => {
  test('should render with default props', () => {
    render(<ActivationStep {...defaultProps} />);
    expect(screen.getByText('step subtitle')).toBeVisible();
    expect(screen.getByRole('button', { name: 'step CTA' })).toBeVisible();
  });
});
