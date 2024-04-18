import JSZip from 'jszip';
import { saveAs } from 'file-saver';

import { copyImgToClipboard, postContent } from './utils';

const imageType = 'image/png';

const getImgBlob = (componentRef) => {
  return new Promise((resolve, reject) => {
    import('html2canvas').then(({ default: html2canvas }) => {
      html2canvas(componentRef.current, {
        allowTaint: true,
        useCORS: true,
      })
        .then((canvas) => {
          canvas.toBlob((blob) => {
            if (blob) {
              resolve(blob);
            } else {
              reject(new Error('Failed to capture the image as blob.'));
            }
          });
        })
        .catch((error) => {
          reject(error);
        });
    });
  });
};

const downloadAllSlides = (capturedSlides) => {
  const zip = new JSZip();
  capturedSlides.forEach((blob, key) => {
    zip.file(`${key}.png`, blob);
  });
  zip.generateAsync({ type: 'blob' }).then((content) => {
    saveAs(content, 'razorpay_rewind.zip');
  });
};

const captureImage = (componentRef, imgDimension, action): Promise<void> | undefined => {
  if (!componentRef.current) {
    return;
  }
  import('html2canvas').then(({ default: html2canvas }) => {
    html2canvas(componentRef.current, {
      allowTaint: true,
      useCORS: true,
      // height: imgDimension + 100,
    }).then((canvas) => {
      canvas.toBlob(
        async (blob) => {
          if (!blob) return;
          if (action === 'copy') {
            await copyImgToClipboard(blob, imageType);
          } else if (action === 'navigator-share') {
            const file = new File([blob], 'razorpay_rewind.png', {
              type: imageType,
            });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
              try {
                await navigator.share({
                  text: postContent,
                  files: [file],
                  title: 'RazorpayRewind',
                });
              } catch (e) {
                console.warn('Error sharing:', e);
              }
            }
          }
        },
        imageType,
        1,
      );
    });
  });
};

export { captureImage, getImgBlob, downloadAllSlides };
