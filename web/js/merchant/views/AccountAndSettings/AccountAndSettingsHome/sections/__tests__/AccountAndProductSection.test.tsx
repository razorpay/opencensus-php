import '@testing-library/jest-dom/extend-expect';
import { defaultProps } from './mocks/fixtures/AccountAndProducts';
import AccountAndProductSection from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/AccountAndProductSection';
import React from 'react';
import { render, screen } from 'test-utils';

describe('Account And Product Section', () => {
  const renderApp = (props) => render(<AccountAndProductSection {...defaultProps} {...props} />);

  test('should render section heading in desktop view', () => {
    renderApp({
      isMobile: false,
    });
    expect(screen.getByText('Account and product settings')).toBeInTheDocument();
  });

  test('should render section cards', () => {
    renderApp({});
    defaultProps.sections.forEach((each) => {
      expect(screen.getByText(each.title)).toBeInTheDocument();
    });
  });

  test('should render divider for every section in mobile', () => {
    renderApp({
      isMobile: true,
    });
    const dividers = screen.getAllByTestId('divider');
    expect(dividers.length).toEqual(defaultProps.sections.length - 1);
  });

  test('should render shimmer incase of section length is zero', () => {
    const props = {
      sections: [],
      isMobile: true,
    };
    const { rerender } = renderApp(props);
    expect(screen.getAllByTestId('skeleton-card-shimmer').length).toEqual(3);
    expect(screen.getAllByTestId('divider').length).toEqual(2);

    rerender(<AccountAndProductSection {...props} isMobile={false} />);
    expect(screen.getAllByTestId('skeleton-card-shimmer').length).toEqual(3);
    expect(screen.getAllByTestId('divider').length).toEqual(3);
  });
});
