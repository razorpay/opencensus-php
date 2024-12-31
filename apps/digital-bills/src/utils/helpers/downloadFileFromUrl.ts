const downloadFileFromUrl = (url: string): void => {
  const link = document.createElement('a');
  link.href = url;
  link.download = '';
  link.target = '_blank';
  link.click();
};

export default downloadFileFromUrl;
