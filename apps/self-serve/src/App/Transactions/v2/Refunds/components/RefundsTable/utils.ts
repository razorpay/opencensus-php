export const getSourceChannelType = (source_channel: string | null) => {
  if (source_channel === 'online') return 'Online';
  else if (source_channel === 'in_person') return 'In Person';
  else return null;
};
