export const fileId = {
  title: 'File Id',
  value: (item: { file_id: string }) => item.file_id || '-',
};

export const countryCode = {
  title: 'Country code',
  columnClass: 'text-left',
  value: (item: { country_code: string }) => item?.country_code || '-',
};

export const createdAt = {
  title: 'Uploaded on',
  columnClass: 'text-right',
  value: (item: { created_at: number }) => {
    if (!item.created_at) return '-';

    const date = new Date(item.created_at * 1000);
    const fullDate = `${date.getDate()} ${date.toLocaleString('default', {
      month: 'short',
    })} ${date.getFullYear()}`;
    return fullDate;
  },
};

export const zipcode = {
  title: 'Zipcode',
  columnClass: 'text-left',
  value: (item: { zipcode: string }) => item?.zipcode || '-',
};
