export const getLeafList = (status) => ({
  listHeader: 'dummy header',
  listDescription: 'dummy description',
  list: [
    {
      name: 'dummy list instrument name 1',
      description: 'dummy list instrument description 1',
      status,
    },
  ],
});

export const getInstrumentData = (status, slug = 'ach') => ({
  icon: 'dummy',
  name: 'ACH transfer',
  description: 'US bank transfer',
  slug,
  status,
});
