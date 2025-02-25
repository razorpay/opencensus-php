/**
 * Utility function to download a file in the browser.
 * 
 * @param data - The data to be downloaded (can be string, Blob, or ArrayBuffer).
 * @param filename - The name of the file to be saved.
 * @param mime - The MIME type of the file (defaults to 'application/octet-stream' if not provided).
 * @param bom - Optional byte order mark (BOM) to prepend to the data.
 * 
 * @example
 * const data = 'Hello World';
 * const filename = 'example.txt';
 * fileDownload(data, filename, 'text/plain');
 */
export const downloadFile = (
    data: string | Blob | ArrayBuffer, 
    filename: string, 
    mime?: string, 
    bom?: Uint8Array
  ): void  => {
    const blobData = typeof bom !== 'undefined' ? [bom, data] : [data];
    const blob = new Blob(blobData, { type: mime || 'application/octet-stream' });
    
    if (typeof window.navigator.msSaveBlob !== 'undefined') {
      window.navigator.msSaveBlob(blob, filename);
    } else {
      const blobURL = window.URL.createObjectURL(blob);
      const tempLink = document.createElement('a');
      tempLink.style.display = 'none';
      tempLink.href = blobURL;
      tempLink.setAttribute('download', filename);
  
      if (typeof tempLink.download === 'undefined') {
        tempLink.setAttribute('target', '_blank');
      }
  
      document.body.appendChild(tempLink);
      tempLink.click();
  
      setTimeout(() => {
        document.body.removeChild(tempLink);
        window.URL.revokeObjectURL(blobURL);
      }, 0);
    }
  }
  