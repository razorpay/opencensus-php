import React from 'react';
import { userEvent, render, screen } from 'test-utils';
import PageLayout from '../PageLayout';

const renderApp = (
  leading = '',
  children = null,
  title = 'Test Title',
  subtitle = 'test Subtitle',
) => {
  render(
    <PageLayout title={title} subtitle={subtitle} leading={leading}>
      {children}
    </PageLayout>,
  );
};

describe('<PageLayout />', () => {
  test('should render', () => {
    renderApp();
    expect(screen.getByTestId('gcms-page-layout')).toBeVisible();
    expect(screen.getByText('Test Title')).toBeVisible();
  });
});
