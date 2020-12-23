import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import FileUpload from '../fileUpload';
import { fireEvent, render, screen } from 'test-utils';

describe('fileUpload component', () => {
  test('Upload button component', () => {
    const file = new File(['(⌐□_□)'], 'abc.png', { type: 'image/png' });
    const fileUploadfn = jest.fn();
    const remove = jest.fn();
    const App = () => (
      <FileUpload
        onFileUpload={fileUploadfn}
        onRemove={remove}
        progress={0}
        accept={[]}
        fileNameProp=""
        name="aadhar_front"
        error=""
      />
    );
    render(<App />, {});
    const uploadBtn = screen.getByTestId('upload-button');
    const uploadInput = screen.getByTestId('upload-input');
    expect(uploadBtn).toBeInTheDocument();
    expect(uploadInput).toBeInTheDocument();

    fireEvent.change(uploadInput, { target: { files: [file] } });
    expect(fileUploadfn).toBeCalledTimes(1);
    expect(screen.getByText('abc.png')).toBeInTheDocument();
    const closeButton = screen.getByTestId('ds-button');
    expect(closeButton).toBeInTheDocument();
    fireEvent.click(closeButton);
    expect(remove).toBeCalledTimes(1);
  });
});
