import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { TimePicker } from 'merchant_common/views/Reports/components';
import moment from 'moment';
import { getInitialTimeStates } from 'merchant_common/views/Reports/components/TimePicker/utils';

const getInitialStates = (date) => {
  const formattedState = getInitialTimeStates(date);
  return date
    .clone()
    .set({
      hour: moment(`${formattedState[0]} ${formattedState[2]}`, 'h A').get('hour'),
      minute: formattedState[1],
    })
    .format('h:mm A');
};

describe('TimePicker', () => {
  const App = () => {
    return (
      <>
        <TimePicker onChange={() => {}} value={moment()} />
        <p aria-label="selected time" />
      </>
    );
  };

  test('should render component without error', () => {
    render(<App />);
    expect(
      screen.getByLabelText(`Selected Time Is ${getInitialStates(moment())}`),
    ).toBeInTheDocument();
    expect(screen.queryByLabelText('Time Picker Container')).not.toBeInTheDocument();
  });

  test('should open picker when clicked', async () => {
    render(<App />);
    await userEvent.click(screen.getByLabelText(`Selected Time Is ${getInitialStates(moment())}`));

    // check if buttons are shown
    expect(screen.getByLabelText('Hour Up')).toBeInTheDocument();
    expect(screen.getByLabelText('Hour Down')).toBeInTheDocument();
    expect(screen.getByLabelText('Minute Up')).toBeInTheDocument();
    expect(screen.getByLabelText('Minute Down')).toBeInTheDocument();
    expect(screen.getByLabelText('Meridiem Up')).toBeInTheDocument();
    expect(screen.getByLabelText('Meridiem Down')).toBeInTheDocument();

    await userEvent.click(screen.getByLabelText('selected time'));
  });

  test('should change time', async () => {
    render(<App />);
    await userEvent.click(screen.getByLabelText(`Selected Time Is ${getInitialStates(moment())}`));

    await userEvent.click(screen.getByLabelText('Hour Up'));
    await userEvent.click(screen.getByLabelText('Hour Down'));

    await userEvent.click(screen.getByLabelText('Minute Up'));
    await userEvent.click(screen.getByLabelText('Minute Down'));

    await userEvent.click(screen.getByLabelText('Meridiem Up'));
    await userEvent.click(screen.getByLabelText('Meridiem Down'));

    // check if values are shown
    expect(screen.getByLabelText(`Hour -> ${moment().format('h')}`)).toHaveTextContent(
      moment().format('h'),
    );
    expect(screen.getByLabelText(`Minute -> ${moment().format('mm')}`)).toHaveTextContent(
      moment().format('mm'),
    );

    expect(screen.getByLabelText(`Meridiem -> ${moment().format('A')}`)).toHaveTextContent(
      moment().format('A'),
    );
  });
});
