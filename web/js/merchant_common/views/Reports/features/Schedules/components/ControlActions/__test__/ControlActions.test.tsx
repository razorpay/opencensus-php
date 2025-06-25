import React from 'react';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import { render, screen, userEvent } from 'test-utils';
import { ControlActions } from 'merchant_common/views/Reports/features/Schedules/components/ControlActions/ControlActions';
import * as modalFn from 'merchant_common/reducers/modals';
import * as trackEvents from 'merchant_common/views/Reports/configs/analytics.config';

const openModal = jest.spyOn(modalFn, 'openModal');
const trackScheduleSection = jest.spyOn(trackEvents, 'trackScheduleSection');

const doesBtnExist = (label) => {
  const refBtn = screen.getByLabelText(label);
  expect(refBtn).toBeInTheDocument();
};

const clickOnControlBtn = async (label) => {
  const refBtn = screen.queryByLabelText(label);
  expect(refBtn).toBeInTheDocument();
  if (refBtn) {
    await userEvent.click(refBtn);
  }
};

describe('ControlActions', () => {
  const App = (props) => {
    return <ControlActions {...props} />;
  };

  it('should render component without any error', () => {
    expect(() =>
      render(
        <App
          scheduleData={{
            status: 'inactive',
            id: 'IOP',
          }}
        />,
      ),
    ).not.toThrow();
    doesBtnExist('Resume Schedule');
  });

  it('should render the required controls', () => {
    render(
      <App
        scheduleData={{
          status: 'active',
          id: 'IOP',
        }}
      />,
    );
    doesBtnExist('Pause Schedule');
    doesBtnExist('Delete Schedule');
  });

  it('should trigger event and open modal on pause click', async () => {
    render(
      <App
        scheduleData={{
          status: 'active',
          id: 'IOP',
        }}
      />,
    );
    await clickOnControlBtn('Pause Schedule');
    expect(trackScheduleSection).toHaveBeenCalled();
    expect(openModal).toHaveBeenCalled();
  });

  it('should trigger event and open modal on resume click', async () => {
    render(
      <App
        scheduleData={{
          status: 'inactive',
          id: 'IOP',
        }}
      />,
    );
    await clickOnControlBtn('Resume Schedule');
    expect(trackScheduleSection).toHaveBeenCalled();
    expect(openModal).toHaveBeenCalled();
  });

  it('should trigger event and open modal on delete click', async () => {
    render(
      <App
        scheduleData={{
          status: 'active',
          id: 'IOP',
        }}
      />,
    );
    await clickOnControlBtn('Delete Schedule');
    expect(trackScheduleSection).toHaveBeenCalled();
    expect(openModal).toHaveBeenCalled();
  });
});
