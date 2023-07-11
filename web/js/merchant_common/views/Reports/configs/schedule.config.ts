export const checkScheduleStatus = (status?: string) => {
  switch (status) {
    case 'paused':
      return 'Paused';
    case 'active':
      return 'Ongoing';
    case 'finished':
      return 'Finished';
    default:
      return '';
  }
};
