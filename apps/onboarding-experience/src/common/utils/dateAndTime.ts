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
