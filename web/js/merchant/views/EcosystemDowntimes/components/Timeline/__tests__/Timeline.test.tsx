import React from 'react';
import { screen, render } from 'test-utils';
import Timeline from 'merchant/views/EcosystemDowntimes/components/Timeline';

describe('<Timeline/>', () => {
  const data = [
    {
      icon: <div aria-label="custom-icon">Test Icon 1 </div>,
      content: 'Some Test content 1 ',
    },
    {
      content: <div aria-label="custom-content">Some Test content 2</div>,
    },
  ];

  test('should render timeline component on screen', () => {
    render(<Timeline data={data} />);
    expect(screen.getByLabelText('timeline')).toHaveTextContent(/Some Test content 1/);
  });

  test('should render timeline items on screen', () => {
    render(<Timeline data={data} />);
    const timelineItems = screen.getAllByLabelText('timeline-item');
    expect(timelineItems).toHaveLength(2);
  });
  test('should render correct connectors numbers on screen', () => {
    render(<Timeline data={data} />);
    const timelineItems = screen.getAllByLabelText('timeline-connector');
    expect(timelineItems).toHaveLength(data.length - 1);
  });

  test('should render custom icon on screen if provided', () => {
    render(<Timeline data={data} />);
    expect(screen.getByLabelText('custom-icon')).toBeInTheDocument();
  });

  test('should render custom content on screen if provided', () => {
    render(<Timeline data={data} />);
    expect(screen.getByLabelText('custom-content')).toHaveTextContent('Some Test content 2');
  });
});
