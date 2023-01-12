import '@testing-library/jest-dom/extend-expect';
import UserInfo from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/UserInfo';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { defaultProps } from './mocks/fixtures/userInfo';

describe('UserInfo', () => {
  const onEditClick = jest.fn();

  const renderApp = (props) =>
    render(<UserInfo onClick={onEditClick} {...defaultProps} {...props} />);

  test.each(defaultProps.infoData)('should render info display name and value', (infoData) => {
    renderApp({});
    expect(screen.getByText(infoData.displayName)).toBeInTheDocument();
    expect(screen.getByText(infoData.value)).toBeInTheDocument();
  });

  test.each(defaultProps.infoData)('should render tooltip with description', (infoData) => {
    renderApp({
      isMobile: false,
    });
    if (infoData.tooltip) {
      expect(screen.getByText(infoData.tooltip.description)).toBeInTheDocument();
    }
  });

  test('onEditClick should be invoked on edit icon click', async () => {
    renderApp({});
    const editIcon = screen.getAllByText(
      (_, element): any =>
        element?.tagName.toLowerCase() === 'i' &&
        element?.getAttribute('class')?.includes('i-icon-container'),
    );
    await userEvent.click(editIcon[0]);
    expect(onEditClick).toHaveBeenCalled();
  });
});
