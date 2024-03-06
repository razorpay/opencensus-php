import { ShowNotificationType } from 'common/typings';

export const downloadFromUrl = (
  showNotification: ShowNotificationType,
  file_link: string,
): void => {
  if (!file_link) {
    showNotification({
      type: 'error',
      message: 'Something went wrong',
    });
    return;
  }
  const link = document.createElement('a');
  link.href = file_link;
  link.setAttribute('download', '');
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
