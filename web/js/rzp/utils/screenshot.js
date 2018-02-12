import html2canvas from 'html2canvas';

const takeScreenshot = node => {
  if (!node) {
    return Promise.reject();
  }

  return html2canvas(node).then(canvas => {
    return canvas.toDataURL();
  });
};

export default takeScreenshot;
