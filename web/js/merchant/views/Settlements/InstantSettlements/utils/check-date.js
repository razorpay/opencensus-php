import moment from 'moment';

export function checkDateIsWeekend(date) {
  const day = date.getDay();
  return day === 6 || day === 0;
}

export function checkDateIsUpcomingBankHoliday(date, holidayList) {
  const currentDayObject = moment(new Date(), 'DD/MM/YYYY').utcOffset('-05:30');

  for (const year in holidayList.data) {
    if (Object.prototype.hasOwnProperty.call(holidayList.data, year)) {
      const holidays = holidayList.data[year];

      for (const holiday of holidays) {
        const holidayDayObject = moment(holiday.date, 'DD/MM/YYYY').utcOffset('-05:30');
        const holidayDayDifference = holidayDayObject.diff(currentDayObject, 'days');

        if (holidayDayDifference < 3 && holidayDayDifference >= 0) {
          return {
            dayDifference: holidayDayDifference,
            date: holiday.date,
            description: holiday.description,
          };
        }
      }
    }
  }

  return false;
}
