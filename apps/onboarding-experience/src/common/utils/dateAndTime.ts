// Get the offset date from time provided in (Month Date) format
export const getETAfromOffset = (date = new Date(), offset = 48 * 60 * 60 * 1000): string => {
  const currentDate = new Date(date);
  const futureDate = new Date(currentDate.getTime() + offset);

  const options: Intl.DateTimeFormatOptions = {
    month: 'short',
    day: 'numeric',
  };

  // output: Nov 8
  const formattedDate = futureDate.toLocaleString('en-US', options);
  return formattedDate;
};

export const getDateSuffix = (date: number) => {
  if (date > 3 && date < 21) return 'th';
  switch (date % 10) {
    case 1:
      return 'st';
    case 2:
      return 'nd';
    case 3:
      return 'rd';
    default:
      return 'th';
  }
};

// returns data in this format: February 7
export function getFormattedDateFromTimestamp(timestamp: number): string {
  const date = new Date(timestamp * 1000);
  return date.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
  });
}
