import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import EntitySchedule from 'merchant/views/Settlements/components/EntitySchedule';
import { render, screen } from 'test-utils';

describe('EntitySchedule', () => {
  const App = (props) => <EntitySchedule {...props} />;

  test('should render entity', () => {
    const EntityType = 'refunds';
    render(<App entityType={EntityType} />);
    const entity = screen.getByText(EntityType);
    expect(entity).toBeInTheDocument();
  });
  test('should render schedule', () => {
    const schedule = 'Entity Schedule';
    render(<App schedule={schedule} />);
    const scheduleText = screen.getByText(schedule);
    expect(scheduleText).toBeInTheDocument();
  });
  test('should render info', () => {
    const info =
      'The fund transfer happens internally as per the given schedule, the credit to linked accounts will happen as per the settlement schedule of the linked accounts.';
    render(<App info={info} />);
    const infoText = screen.getByText(info);
    expect(infoText).toBeInTheDocument();
  });
});
