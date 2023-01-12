import '@testing-library/jest-dom/extend-expect';
import * as trackEvents from 'common/utils/analytics';
import SectionCard from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/SectionCard';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { defaultProps } from './mocks/fixtures/sectionCard';

describe('SectionCard', () => {
  const analyticsTrackWithUserSpy = jest.spyOn(trackEvents, 'analyticsTrackWithUserInfo');

  const renderApp = (props) => render(<SectionCard {...defaultProps} {...props} />);

  test('should render card heading and icon', () => {
    renderApp({});
    expect(screen.getByText(defaultProps.title)).toBeInTheDocument();

    const cardIcon = screen.getByText((_, element) => element?.tagName.toLowerCase() === 'i');
    expect(cardIcon).toBeInTheDocument();
    expect(cardIcon).toHaveAttribute('class', `i ${defaultProps.icon}`);
  });

  test('should render divider incase of desktop', () => {
    renderApp({
      isMobile: false,
    });
    expect(screen.getByTestId('divider')).toBeInTheDocument();
  });

  test.each(defaultProps.subSections)('should render sub sections in card', (subSection) => {
    renderApp({});
    expect(screen.getByText(subSection.title)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: subSection.title })).toBeInTheDocument();
  });

  test('should call analytics track on click of sub section', async () => {
    renderApp({});
    const selectedSubSection = defaultProps.subSections[2];
    const subSectionItem = screen.getByRole('button', { name: selectedSubSection.title });
    expect(subSectionItem).toBeInTheDocument();

    await userEvent.click(subSectionItem);
    expect(analyticsTrackWithUserSpy).toBeCalledTimes(1);
    expect(analyticsTrackWithUserSpy).toHaveBeenCalledWith({
      objectName: 'Business Profile',
      actionName: 'Clicked',
      screen: 'Account & Settings',
      properties: {
        clickedElement: selectedSubSection.title,
        section: selectedSubSection.title,
      },
    });
  });
});
