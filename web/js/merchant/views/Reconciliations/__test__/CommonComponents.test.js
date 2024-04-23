import {
  SomethingWrong,
  FileUploadStatus,
  RenderErrorLoadingOrChild,
} from 'merchant/views/Reconciliations/commonComponents';
import { render } from 'test-utils';

const renderError = (props = {}) => {
  render(<SomethingWrong {...props} />);
};
const renderConditionalError = (props = {}) => {
  render(<RenderErrorLoadingOrChild {...props} />);
};

describe('Tests for common components - Recon Saas', () => {
  test('Should render api failure component without errors', () => {
    expect(renderError).not.toThrowError();
  });
  test('renders heading, and text correctly', () => {
    const { getByText } = render(<SomethingWrong />);

    const heading = getByText('Something went wrong');
    expect(heading).toBeInTheDocument();

    const textMessage = getByText('Please try again after some time');
    expect(textMessage).toBeInTheDocument();
  });

  test('renders nothing when no file is not uploading or uploaded', () => {
    const fileData = {
      isUploading: false,
      isUploaded: false,
      fileName: 'example.txt',
    };
    const { queryByText, queryByAltText } = render(<FileUploadStatus fileData={fileData} />);
    expect(queryByAltText('File Uploading')).not.toBeInTheDocument();
    expect(queryByText('Error Occured')).not.toBeInTheDocument();
    expect(queryByText('example.txt')).not.toBeInTheDocument();
  });

  test('renders file upload status correctly when uploading', () => {
    const fileData = {
      isUploading: true,
      fileName: 'example.txt',
      error: false,
    };
    const { getByText, getByAltText } = render(<FileUploadStatus fileData={fileData} />);

    const fileNameText = getByText('example.txt');
    expect(fileNameText).toBeInTheDocument();

    const fileUploadingImage = getByAltText('File Uploading');
    expect(fileUploadingImage).toBeInTheDocument();
  });

  test('renders file upload status correctly when uploaded', () => {
    const fileData = {
      isUploaded: true,
      fileName: 'example.txt',
      error: false,
    };
    const { getByText, getByAltText } = render(<FileUploadStatus fileData={fileData} />);

    const fileNameText = getByText('example.txt');
    expect(fileNameText).toBeInTheDocument();

    const fileUploadedImage = getByAltText('File Uploading');
    expect(fileUploadedImage).toBeInTheDocument();
  });

  test('renders error message when file upload has error', () => {
    const fileData = {
      error: true,
      isUploaded: true,
    };
    const { getByText } = render(<FileUploadStatus fileData={fileData} />);

    const errorMessage = getByText('Error Occured');
    expect(errorMessage).toBeInTheDocument();
  });

  test('renders error wrapper component without errors', () => {
    expect(renderConditionalError).not.toThrowError();
  });

  test('renders children when there is no error and no loading', () => {
    const { queryByText } = render(
      <RenderErrorLoadingOrChild isError={false} isLoading={false}>
        <div>Hello World</div>
      </RenderErrorLoadingOrChild>,
    );
    expect(queryByText('Hello World')).toBeInTheDocument();
    expect(queryByText('Something went wrong')).not.toBeInTheDocument();
  });

  test('renders SomethingWrong component when there is an error', () => {
    const { queryByText } = render(
      <RenderErrorLoadingOrChild isError={true} isLoading={false}>
        <div>Hello World</div>
      </RenderErrorLoadingOrChild>,
    );
    expect(queryByText('Something went wrong')).toBeInTheDocument();
    expect(queryByText('Hello World')).not.toBeInTheDocument();
  });

  test('renders Loader component when isLoading is true', () => {
    const { container } = render(
      <RenderErrorLoadingOrChild isLoading={true} isError={false}>
        <div>Hello World</div>
      </RenderErrorLoadingOrChild>,
    );
    const spinner = container.querySelector("[data-blade-component='spinner']");
    expect(spinner).toBeInTheDocument();
  });
});
