import FAQs from 'merchant/views/Settlements/v3/components/FAQs';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { useResizeLayout } from 'common/hooks/useResizeLayout';
import { Queries } from 'merchant/views/Settlements/v3/components/FAQs/FAQQueries';

jest.mock('common/hooks/useResizeLayout', () => ({
  useResizeLayout: jest.fn(),
}));

const renderApp = () => render(<FAQs />);

describe('FAQs', () => {
  const clickFaqQuery = async (query) => {
    const queryTab = screen.getAllByText(query)[0];
    await userEvent.click(queryTab);
  };

  test('should render FAQs header', () => {
    renderApp();
    expect(screen.getByText('FAQs')).toBeInTheDocument();
  });

  test.each(Queries)('should render %s faq in desktop device', async ({ query }) => {
    // eslint-disable-next-line
    // @ts-ignore
    useResizeLayout.mockReturnValue(false);
    renderApp();
    await clickFaqQuery(query);
    expect(screen.getAllByText(query).length >= 1).toBeTruthy();
  });

  test.each(Queries)(
    'should render %s faq in mobile device with collapsible',
    async ({ query }) => {
      // eslint-disable-next-line
      // @ts-ignore
      useResizeLayout.mockReturnValue(true);
      renderApp();
      await clickFaqQuery(query);
      expect(screen.getByText(query)).toBeInTheDocument();
    },
  );

  test('should render first faq when resize layout occur from mobile to desktop', async () => {
    // eslint-disable-next-line
    // @ts-ignore
    useResizeLayout.mockReturnValue(true);
    const { rerender } = renderApp();
    const queryTab = screen.getByText(Queries[0].query);
    await userEvent.click(queryTab);
    // eslint-disable-next-line
    // @ts-ignore
    useResizeLayout.mockReturnValue(false);
    rerender(<FAQs />);
    expect(screen.getAllByText(Queries[0].query).length).toEqual(2);
  });
});
