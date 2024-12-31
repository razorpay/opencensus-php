import downloadFileFromUrl from '@apps/digital-bills/src/utils/helpers/downloadFileFromUrl';

const mocks = {
  downloadUrl: '',
  linkElement: {
    href: '',
    click: jest.fn(),
    download: '',
    target: '',
  },
};

describe('downloadFileFromUrl', () => {
  test('should download a file', () => {
    const createElementSpy = jest
      .spyOn(document, 'createElement')
      .mockReturnValueOnce(mocks.linkElement as unknown as HTMLElement);
    downloadFileFromUrl(mocks.downloadUrl);
    expect(createElementSpy).toHaveBeenCalledWith('a');
    expect(mocks.linkElement.click).toHaveBeenCalled();
  });
});
