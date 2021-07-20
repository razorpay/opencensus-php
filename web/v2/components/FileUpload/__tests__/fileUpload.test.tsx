import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import FileUpload from '../fileUpload';
import { fireEvent, render, screen } from 'test-utils';

describe('fileUpload component', () => {
  test('Upload button component', () => {
    const file = new File(['(⌐□_□)'], 'abc.png', { type: 'image/png' });
    const fileUploadfn = jest.fn();
    const deleteFile = jest.fn();
    const App = () => (
      <FileUpload
        onFileUpload={fileUploadfn}
        onRemove={deleteFile}
        progress={0}
        accept={[]}
        value=""
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
    const closeButton = screen.getByTestId('ds-fileUpload');
    expect(closeButton).toBeInTheDocument();
    fireEvent.click(closeButton);
    expect(deleteFile).toBeCalledTimes(1);
  });

  test('Do not upload in case of disable', () => {
    const file = new File(['(⌐□_□)'], 'abc.png', { type: 'image/png' });
    const fileUploadfn = jest.fn();
    const deleteFile = jest.fn();
    const App = () => (
      <FileUpload
        onFileUpload={fileUploadfn}
        onRemove={deleteFile}
        progress={0}
        accept={[]}
        value=""
        name="aadhar_front"
        error=""
        disabled={true}
      />
    );
    render(<App />, {});
    const uploadBtn = screen.getByTestId('upload-button');
    const uploadInput = screen.getByTestId('upload-input');
    expect(uploadBtn).toBeInTheDocument();
    expect(uploadInput).toBeInTheDocument();

    fireEvent.change(uploadInput, { target: { files: [file] } });
    expect(fileUploadfn).toBeCalledTimes(0);
    expect(screen.queryByText('abc.png')).not.toBeInTheDocument();
  });
});

test('reset fileupload in case of error', () => {
  const fileUploadfn = jest.fn();
  const deleteFile = jest.fn();
  const App = () => (
    <FileUpload
      onFileUpload={fileUploadfn}
      onRemove={deleteFile}
      progress={0}
      accept={[]}
      value=""
      name="aadhar_front"
      error="file size to large"
    />
  );
  render(<App />, {});
  const uploadBtn = screen.getByTestId('upload-button');
  const uploadInput = screen.getByTestId('upload-input');
  expect(uploadBtn).toBeInTheDocument();
  expect(uploadInput).toBeInTheDocument();
});
