import React from 'react';

import { renderWithSuspense, screen, waitFor } from 'test-utils';

import ProductInsight from '../ProductInsight';

const renderApp = ({ actionClickHandler }: { actionClickHandler?: () => void }) =>
  renderWithSuspense(
    <ProductInsight
      displayText="Display Text"
      badges={['Badge 1']}
      logoImages={[
        'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/bose.png',
        'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/bose.png',
      ]}
      aboveDisplayHeading="Above display heading"
      belowDisplayHeading="Below display heading"
      actionCta="Action CTA"
      captionList={['Caption List Item 1']}
      bodyText="Body text"
      postPitchCaption="Post pitch caption"
      actionClickHandler={actionClickHandler}
    />,
  );

describe('ProductInsight', () => {
  it('should render component correctly', async () => {
    renderApp({});
    await waitFor(() => {
      expect(screen.getByText('Display Text')).toBeInTheDocument();
      expect(screen.getByText('Badge 1')).toBeInTheDocument();
      expect(screen.getByText('Above display heading')).toBeInTheDocument();
      expect(screen.getByText('Below display heading')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'Action CTA' })).toBeInTheDocument();
      expect(screen.getByText('Caption List Item 1')).toBeInTheDocument();
      expect(screen.getByText('Body text')).toBeInTheDocument();
      expect(screen.getByText('Post pitch caption')).toBeInTheDocument();
    });
  });

  it('should call actionClickHandler when action CTA is clicked', async () => {
    const actionClickHandler = jest.fn();
    renderApp({ actionClickHandler });
    await waitFor(() => {
      const actionCta = screen.getByRole('button', { name: 'Action CTA' });
      actionCta.click();
      expect(actionClickHandler).toHaveBeenCalled();
    });
  });

  it('should show the corrent number of images', async () => {
    renderApp({});
    await waitFor(() => {
      expect(screen.getAllByAltText('Brand logo')).toHaveLength(2);
    });
  });
});
