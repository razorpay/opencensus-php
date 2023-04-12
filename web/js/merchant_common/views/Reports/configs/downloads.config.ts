export const checkDownloadsLogStatus = (status: string, fileId: string | null | undefined) => {
  switch (status) {
    case 'created':
    case 'processing':
    case 'retrying':
      return 'Pending';
    case 'processed':
      return fileId ? 'Success' : 'No Data';
    case 'failed':
      return 'Failed';
    default:
      return '';
  }
};
